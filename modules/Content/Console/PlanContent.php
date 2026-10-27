<?php

declare(strict_types=1);

namespace Modules\Content\Console;

use Catch\Enums\Status;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Enums\TopicStatus;
use Modules\Content\Jobs\PlanTopics;
use Modules\Content\Models\Article;
use Modules\Content\Models\Setting;
use Modules\Content\Models\Topic;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Enums\ProfileStatus;
use Modules\Site\Models\Site;

/**
 * 每小时运行：补足选题，按每周配额让每个站一次生成一篇文章
 */
class PlanContent extends Command
{
    protected $signature = 'content:plan {--force : 未开启自动生产时也执行}';

    protected $description = '为已上线且游戏资料已确认的站点补充选题、安排文章生成';

    public function handle(): int
    {
        $settings = Setting::values();
        if (! $settings['auto_enabled'] && ! $this->option('force')) {
            $this->info('自动生产未开启');

            return self::SUCCESS;
        }

        $weekAgo = time() - 7 * 86400;
        $planned = $written = 0;

        static::readySites()->select('id')->chunkById(200, function ($sites) use ($settings, $weekAgo, &$planned, &$written) {
            foreach ($sites as $site) {
                $pending = Topic::query()->where('site_id', $site->id)->where('status', TopicStatus::Pending->value)->count();
                if ($pending < $settings['topic_backlog']) {
                    PlanTopics::dispatch($site->id, $settings['topic_backlog'] - $pending);
                    $planned++;
                }

                $articles = Article::query()->where('site_id', $site->id);
                $writing = (clone $articles)->where('status', ArticleStatus::Writing->value)->exists();
                $thisWeek = (clone $articles)->where('source', 1)->where('created_at', '>=', $weekAgo)->count();
                if ($writing || $thisWeek >= $settings['weekly_articles_per_site']) {
                    continue;
                }

                $topic = Topic::query()->where('site_id', $site->id)->where('status', TopicStatus::Pending->value)->orderBy('id')->first();
                if ($topic) {
                    Topic::writeArticle($topic->id);
                    $written++;
                }
            }
        });

        $this->info("补充选题 {$planned} 个站，安排文章 {$written} 篇");

        return self::SUCCESS;
    }

    /**
     * 已上线、已启用、游戏资料已确认的站点
     */
    public static function readySites(): Builder
    {
        return Site::query()
            ->where('status', Status::Enable->value)
            ->where('onboard_status', OnboardStatus::Online->value)
            ->whereHas('profile', fn ($query) => $query->where('status', ProfileStatus::Confirmed->value));
    }
}
