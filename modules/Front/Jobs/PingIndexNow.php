<?php

declare(strict_types=1);

namespace Modules\Front\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\Front\Models\FrontSetting;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;

/**
 * 把变化的 URL 推送给 IndexNow（Bing、Yandex、Seznam 等共享）
 */
class PingIndexNow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<int, string>  $paths
     */
    public function __construct(
        public readonly int $siteId,
        public readonly array $paths
    ) {
    }

    public function backoff(): array
    {
        return [60, 600];
    }

    public function handle(): void
    {
        $site = Site::query()->find($this->siteId);
        if (! FrontSetting::get('indexnow_enabled') || ! $site || $site->onboard_status !== OnboardStatus::Online->value
            || ! GameProfile::query()->where('site_id', $site->id)->first()?->isConfirmed()) {
            return;
        }

        $key = FrontSetting::indexNowKey();
        Http::timeout(20)->post('https://api.indexnow.org/indexnow', [
            'host' => $site->domain,
            'key' => $key,
            'keyLocation' => "https://{$site->domain}/{$key}.txt",
            'urlList' => array_map(fn ($path) => "https://{$site->domain}/".ltrim($path, '/'), $this->paths),
        ])->throwIf(fn ($response) => $response->serverError() || $response->status() === 429);
    }
}
