<?php

declare(strict_types=1);

namespace Modules\Content\Providers;

use Catch\Providers\CatchModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Content\Console\PlanContent;
use Modules\Content\Console\PublishArticles;

class ContentServiceProvider extends CatchModuleServiceProvider
{
    protected array $commands = [
        PlanContent::class,
        PublishArticles::class,
    ];

    public function boot(): void
    {
        // 需要服务器上配置 * * * * * php artisan schedule:run
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('content:plan')->hourly()->withoutOverlapping()->runInBackground();
            $schedule->command('content:publish')->everyFifteenMinutes()->withoutOverlapping();
        });
    }

    /**
     * route path
     */
    public function moduleName(): string
    {
        return 'content';
    }
}
