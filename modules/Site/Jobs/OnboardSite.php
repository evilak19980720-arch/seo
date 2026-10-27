<?php

declare(strict_types=1);

namespace Modules\Site\Jobs;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\Site;
use Throwable;

/**
 * 站点接入 Cloudflare：Zone → 等待 NS 生效 → 写 DNS 和 SSL 设置 → 等待边缘证书 → 上线
 *
 * 每一步都可重入，等待中的步骤通过 release 延迟重试
 */
class OnboardSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // 等待 NS 生效的检查间隔（秒）
    protected const NS_CHECK_DELAY = 600;

    // 等待证书签发的检查间隔（秒）
    protected const SSL_CHECK_DELAY = 120;

    protected const SSL_MODE = 'full';

    public int $maxExceptions = 5;

    public function __construct(
        public readonly int $siteId
    ) {
    }

    /**
     * NS 修改最长可能需要 1–2 天生效，给足 3 天
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(3);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        $site = Site::query()->with(['cloudflareAccount'])->find($this->siteId);

        if (! $site || $site->onboard_status === OnboardStatus::Online->value) {
            return;
        }

        if (! $site->ip || ! $site->cloudflareAccount) {
            $this->fail(new \RuntimeException('站点缺少解析 IP 或 Cloudflare 账号'));

            return;
        }

        $account = $site->cloudflareAccount;
        $cloudflare = $account->client();

        // 1. Zone：已存在就复用，否则新建
        if (! $site->zone_id) {
            $zone = $cloudflare->findZone($account->account_id, $site->domain)
                ?? $cloudflare->createZone($account->account_id, $site->domain);

            $site->update([
                'zone_id' => $zone['id'],
                'name_servers' => $zone['name_servers'] ?? [],
                'onboard_status' => OnboardStatus::WaitingNameServers->value,
                'onboard_error' => '',
            ]);
        }

        // 2. 等待域名的 NS 指向 Cloudflare
        $zone = $cloudflare->getZone($site->zone_id);
        if (($zone['status'] ?? '') !== 'active') {
            $site->update([
                'name_servers' => $zone['name_servers'] ?? $site->name_servers,
                'onboard_status' => OnboardStatus::WaitingNameServers->value,
            ]);
            $this->release(self::NS_CHECK_DELAY);

            return;
        }

        // 3. 写 DNS 和 SSL 设置（重复执行也安全）
        if ($site->onboard_status !== OnboardStatus::DnsReady->value) {
            $cloudflare->pointDomain($site->zone_id, $site->domain, $site->ip);
            // Full：Cloudflare 到源站加密但不校验证书，源站用自签或 Origin CA 证书都可以
            // 以后为每个域名签发 Origin CA 证书后可改为 strict
            $cloudflare->setting($site->zone_id, 'ssl', self::SSL_MODE);
            $cloudflare->setting($site->zone_id, 'always_use_https', 'on');

            $site->update(['onboard_status' => OnboardStatus::DnsReady->value, 'onboard_error' => '']);
        }

        // 4. 等待边缘证书
        if (! $cloudflare->certificateActive($site->zone_id)) {
            $this->release(self::SSL_CHECK_DELAY);

            return;
        }

        $site->update([
            'onboard_status' => OnboardStatus::Online->value,
            'onboard_error' => '',
            'online_at' => time(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Site::query()->whereKey($this->siteId)->update([
            'onboard_status' => OnboardStatus::Failed->value,
            'onboard_error' => mb_substr($exception?->getMessage() ?: '接入超时，请检查域名 NS 是否已改为 Cloudflare', 0, 1000),
        ]);
    }
}
