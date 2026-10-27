<?php

declare(strict_types=1);

namespace Modules\Front\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\Site;

/**
 * 清除站点在 Cloudflare 上缓存的页面
 */
class PurgeEdgeCache implements ShouldQueue
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
        return [30, 120];
    }

    public function handle(): void
    {
        $site = Site::query()->with('cloudflareAccount')->find($this->siteId);
        if (! $site?->zone_id || ! $site->cloudflareAccount || $site->onboard_status !== OnboardStatus::Online->value) {
            return;
        }

        $urls = array_map(fn ($path) => 'https://'.$site->domain.'/'.ltrim($path, '/'), $this->paths);
        $site->cloudflareAccount->client()->purgeFiles($site->zone_id, $urls);
    }
}
