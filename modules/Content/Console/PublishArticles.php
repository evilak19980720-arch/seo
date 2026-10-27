<?php

declare(strict_types=1);

namespace Modules\Content\Console;

use Illuminate\Console\Command;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Models\Article;
use Modules\Content\Models\Setting;

/**
 * 每 15 分钟运行：把审核通过的文章按每站每日配额逐篇发布
 */
class PublishArticles extends Command
{
    protected $signature = 'content:publish';

    protected $description = '按每日配额发布待发布的文章';

    public function handle(Article $model): int
    {
        $settings = Setting::values();
        if (! $settings['auto_enabled']) {
            return self::SUCCESS;
        }

        $today = strtotime('today');
        $published = 0;

        PlanContent::readySites()
            ->whereIn('id', Article::query()->select('site_id')->where('status', ArticleStatus::Queued->value))
            ->select('id')
            ->chunkById(200, function ($sites) use ($model, $settings, $today, &$published) {
                foreach ($sites as $site) {
                    $count = Article::query()->where('site_id', $site->id)->where('status', ArticleStatus::Published->value)->where('published_at', '>=', $today)->count();
                    if ($count >= $settings['daily_publish_per_site']) {
                        continue;
                    }

                    $article = Article::query()->where('site_id', $site->id)->where('status', ArticleStatus::Queued->value)->orderBy('id')->first();
                    if ($article) {
                        $model->publish($article->id);
                        $published++;
                    }
                }
            });

        $this->info("发布 {$published} 篇");

        return self::SUCCESS;
    }
}
