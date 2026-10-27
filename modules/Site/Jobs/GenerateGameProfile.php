<?php

declare(strict_types=1);

namespace Modules\Site\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Ai\Enums\PromptType;
use Modules\Ai\Support\Ai;
use Modules\Site\Enums\ProfileStatus;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Throwable;

/**
 * 根据网站名称和游戏说明生成游戏资料草稿，并补全站点空着的标题、描述、关键词
 */
class GenerateGameProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $siteId
    ) {
    }

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(Ai $ai): void
    {
        $site = Site::query()->find($this->siteId);
        if (! $site) {
            return;
        }

        $profile = GameProfile::forSite($site->id);
        if ($profile->isConfirmed()) {
            return;
        }

        $data = $ai->run(PromptType::GameProfile, [
            'site_name' => $site->name,
            'locale' => $site->locale,
            'game_brief' => $site->game_brief ?: '(none)',
        ], $site->id)->json();

        $faq = collect($data['faq'] ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['q']) && ! empty($item['a']))
            ->map(fn ($item) => ['q' => (string) $item['q'], 'a' => (string) $item['a']])
            ->values()->all();

        $profile->update([
            'genre' => Str::limit((string) ($data['genre'] ?? ''), 100, ''),
            'summary' => (string) ($data['summary'] ?? ''),
            'gameplay' => (string) ($data['gameplay'] ?? ''),
            'features' => array_values(array_filter(array_map('strval', (array) ($data['features'] ?? [])))),
            'faq' => $faq,
            'status' => ProfileStatus::Draft->value,
            'error' => '',
        ]);

        // 只补全空字段，不覆盖人工填写的内容
        $site->update(array_filter([
            'title' => $site->title ?: Str::limit((string) ($data['title'] ?? ''), 191, ''),
            'description' => $site->description ?: Str::limit((string) ($data['description'] ?? ''), 500, ''),
            'keywords' => $site->keywords ?: Str::limit((string) ($data['keywords'] ?? ''), 500, ''),
        ]));
    }

    public function failed(?Throwable $exception): void
    {
        GameProfile::query()->where('site_id', $this->siteId)->update([
            'status' => ProfileStatus::Failed->value,
            'error' => mb_substr($exception?->getMessage() ?: '生成失败', 0, 1000),
        ]);
    }
}
