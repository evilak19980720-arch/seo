<?php

declare(strict_types=1);

namespace Modules\Site\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Site\Models\Site;

/**
 * 站点 IP 修改后更新 Cloudflare 解析
 */
class SyncSiteDns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $siteId
    ) {
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $site = Site::query()->with('cloudflareAccount')->find($this->siteId);
        if (! $site?->zone_id || ! $site->ip || ! $site->cloudflareAccount) {
            return;
        }

        $site->cloudflareAccount->client()->pointDomain($site->zone_id, $site->domain, $site->ip);
    }
}
