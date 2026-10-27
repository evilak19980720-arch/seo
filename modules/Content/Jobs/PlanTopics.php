<?php

declare(strict_types=1);

namespace Modules\Content\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Support\Ai;
use Modules\Content\Enums\TopicStatus;
use Modules\Content\Models\Article;
use Modules\Content\Models\Topic;
use Modules\Content\Support\Text;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;

/**
 * 根据游戏资料为站点生成选题，跳过与已有文章、选题重复的标题
 */
class PlanTopics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly int $siteId,
        public readonly int $count = 5
    ) {
    }

    public function handle(Ai $ai): int
    {
        $site = Site::query()->find($this->siteId);
        $profile = GameProfile::query()->where('site_id', $this->siteId)->first();
        if (! $site || ! $profile?->isConfirmed() || $this->count < 1) {
            return 0;
        }

        $existing = static::existingTitles($site->id);

        $data = $ai->run(PromptType::Topic, [
            'site_name' => $site->name,
            'locale' => $site->locale,
            'game_profile' => json_encode($profile->facts(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'existing_titles' => $existing ? '- '.implode("\n- ", $existing) : '(none)',
            'count' => (string) $this->count,
        ], $site->id)->json();

        $seen = array_flip(array_map(fn ($title) => Text::slug($title), $existing));
        $created = 0;
        foreach ((array) ($data['topics'] ?? []) as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $key = Text::slug($title);
            if ($title === '' || $key === '' || isset($seen[$key]) || $created >= $this->count) {
                continue;
            }
            $seen[$key] = true;

            Topic::query()->create([
                'site_id' => $site->id,
                'title' => Str::limit($title, 255, ''),
                'keyword' => Str::limit(trim((string) ($item['keyword'] ?? '')), 191, ''),
                'intent' => Str::limit(trim((string) ($item['intent'] ?? '')), 50, ''),
                'status' => TopicStatus::Pending->value,
                'source' => 1,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * 本站已有的文章标题和未废弃的选题标题
     *
     * @return array<int, string>
     */
    public static function existingTitles(int $siteId, int $limit = 200): array
    {
        $articles = Article::query()->where('site_id', $siteId)->latest('id')->limit($limit)->pluck('title')->all();
        $topics = Topic::query()->where('site_id', $siteId)->where('status', '!=', TopicStatus::Dropped->value)->latest('id')->limit($limit)->pluck('title')->all();

        return array_values(array_unique(array_filter([...$articles, ...$topics])));
    }
}
