<?php

declare(strict_types=1);

namespace Modules\Stats\Providers;

use Catch\Providers\CatchModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Events\Terminating;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Front\Http\Middleware\ResolveSite;
use Modules\Front\Support\CurrentSite;
use Modules\Stats\Console\PruneCrawls;
use Modules\Stats\Console\PullCloudflare;
use Modules\Stats\Http\Controllers\TrackController;
use Modules\Stats\Support\Crawler;
use Modules\Stats\Support\Stats;

class StatsServiceProvider extends CatchModuleServiceProvider
{
    /** 当前请求待记录的蜘蛛抓取，响应发出后再做 DNS 验证 */
    protected ?array $pendingCrawl = null;

    protected array $commands = [
        PullCloudflare::class,
        PruneCrawls::class,
    ];

    public function boot(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('stats:cloudflare')->dailyAt('03:10')->withoutOverlapping()->runInBackground();
            $schedule->command('stats:prune')->dailyAt('04:00');
        });

        $this->recordCrawls();

        if (! $this->app->routesAreCached()) {
            // 前台访问量、按钮点击上报
            Route::middleware([ResolveSite::class])->post('t', TrackController::class);
        }
    }

    /**
     * 记录前台页面的蜘蛛抓取。DNS 验证放在响应发出之后，不影响页面速度
     */
    protected function recordCrawls(): void
    {
        Event::listen(RequestHandled::class, function (RequestHandled $event) {
            $this->pendingCrawl = null;
            if (! $this->app->bound(CurrentSite::class) || ! $bot = Crawler::claimed((string) $event->request->userAgent())) {
                return;
            }

            $this->pendingCrawl = [
                'site_id' => $this->app->make(CurrentSite::class)->site->id,
                'bot' => $bot,
                'path' => Str::limit('/'.ltrim($event->request->getRequestUri(), '/'), 500, ''),
                'status' => $event->response->getStatusCode(),
                'ip' => (string) ($event->request->header('CF-Connecting-IP') ?: $event->request->ip()),
            ];
        });

        Event::listen(Terminating::class, function () {
            [$crawl, $this->pendingCrawl] = [$this->pendingCrawl, null];
            if (! $crawl || ! Crawler::verify($crawl['bot'], $crawl['ip'])) {
                return;
            }

            Stats::add($crawl['site_id'], $crawl['bot'] === 'google' ? Stats::CRAWL_GOOGLE : Stats::CRAWL_BING);
            DB::table('stats_crawls')->insert($crawl + ['created_at' => time()]);
        });
    }

    public function moduleName(): string
    {
        return 'stats';
    }
}
