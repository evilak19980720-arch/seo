<?php

declare(strict_types=1);

namespace Modules\Site\Support;

use Catch\Exceptions\FailedException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare API v4 的最小封装，只包含站点接入用到的接口
 */
class Cloudflare
{
    protected const BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        protected readonly string $token
    ) {
    }

    /**
     * 校验 Token。账户令牌（cfat_ 开头）只能用账户下的校验接口，用户令牌用 /user 接口
     */
    public function verifyToken(?string $accountId = null): bool
    {
        if ($accountId) {
            try {
                return ($this->request('get', "/accounts/{$accountId}/tokens/verify")['status'] ?? '') === 'active';
            } catch (FailedException) {
                // 不是这个账户的令牌，再按用户令牌校验
            }
        }

        return ($this->request('get', '/user/tokens/verify')['status'] ?? '') === 'active';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findZone(string $accountId, string $domain): ?array
    {
        $zones = $this->request('get', '/zones', ['name' => $domain, 'account.id' => $accountId]);

        return $zones[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function createZone(string $accountId, string $domain): array
    {
        return $this->request('post', '/zones', [
            'name' => $domain,
            'account' => ['id' => $accountId],
            'type' => 'full',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getZone(string $zoneId): array
    {
        return $this->request('get', "/zones/{$zoneId}");
    }

    /**
     * 新增或更新一条 DNS 记录，默认开启代理（橙色云）
     */
    public function upsertRecord(string $zoneId, string $type, string $name, string $content, bool $proxied = true): void
    {
        $payload = ['type' => $type, 'name' => $name, 'content' => $content, 'proxied' => $proxied, 'ttl' => 1];
        $existing = $this->request('get', "/zones/{$zoneId}/dns_records", ['type' => $type, 'name' => $name]);

        if (isset($existing[0]['id'])) {
            $this->request('put', "/zones/{$zoneId}/dns_records/{$existing[0]['id']}", $payload);
        } else {
            $this->request('post', "/zones/{$zoneId}/dns_records", $payload);
        }
    }

    public function setting(string $zoneId, string $setting, string $value): void
    {
        $this->request('patch', "/zones/{$zoneId}/settings/{$setting}", ['value' => $value]);
    }

    /**
     * 边缘证书是否已签发（任一证书包为 active 即可）
     */
    public function certificateActive(string $zoneId): bool
    {
        $packs = $this->request('get', "/zones/{$zoneId}/ssl/certificate_packs", ['status' => 'all']);

        return collect(is_array($packs) ? $packs : [])->contains(fn ($pack) => ($pack['status'] ?? '') === 'active');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    /**
     * 按 URL 清除 Cloudflare 缓存（每次最多 30 个）
     *
     * @param  array<int, string>  $urls
     */
    public function purgeFiles(string $zoneId, array $urls): void
    {
        foreach (array_chunk(array_values(array_unique($urls)), 30) as $chunk) {
            $this->request('post', "/zones/{$zoneId}/purge_cache", ['files' => $chunk]);
        }
    }

    /**
     * GraphQL 分析接口（Token 需要 Analytics Read 权限）
     */
    public function graphql(string $query, array $variables = []): array
    {
        $response = $this->http()->post(self::BASE_URL.'/graphql', ['query' => $query, 'variables' => $variables]);
        $body = $response->json() ?? [];

        if (! $response->successful() || ! empty($body['errors'])) {
            $message = collect($body['errors'] ?? [])->pluck('message')->implode('; ');

            throw new FailedException('Cloudflare 分析接口错误：'.($message ?: 'HTTP '.$response->status()));
        }

        return $body['data'] ?? [];
    }

    protected function request(string $method, string $path, array $data = []): mixed
    {
        $response = $this->http()->{$method}(self::BASE_URL.$path, $data);
        $body = $response->json() ?? [];

        if (! $response->successful() || ! ($body['success'] ?? false)) {
            $message = collect($body['errors'] ?? [])->map(fn ($error) => ($error['code'] ?? '').' '.($error['message'] ?? ''))->implode('; ');

            throw new FailedException('Cloudflare 接口错误：'.($message ?: 'HTTP '.$response->status()));
        }

        return $body['result'] ?? null;
    }

    protected function http(): PendingRequest
    {
        return Http::withToken($this->token)->acceptJson()->timeout(20)->retry(2, 1000, throw: false);
    }
}
