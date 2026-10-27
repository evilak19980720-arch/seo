<?php

declare(strict_types=1);

namespace Modules\Stats\Support;

use Illuminate\Support\Facades\Cache;

/**
 * 识别 Googlebot / Bingbot，并用反向 + 正向 DNS 验证，排除伪装的 UA
 */
class Crawler
{
    /** UA 关键字 => [名称, 允许的反解域名后缀] */
    protected const BOTS = [
        'googlebot' => ['google', ['.googlebot.com', '.google.com', '.googleusercontent.com']],
        'google-inspectiontool' => ['google', ['.googlebot.com', '.google.com']],
        'bingbot' => ['bing', ['.search.msn.com']],
    ];

    /**
     * UA 声称的蜘蛛名称，不是蜘蛛返回 null
     */
    public static function claimed(string $userAgent): ?string
    {
        $ua = strtolower($userAgent);
        foreach (self::BOTS as $needle => [$name]) {
            if (str_contains($ua, $needle)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * 验证 IP 是否真的属于该蜘蛛，结果按 IP 缓存一天
     */
    public static function verify(string $bot, string $ip): bool
    {
        return (bool) Cache::remember("stats:crawler:{$bot}:{$ip}", 86400, function () use ($bot, $ip) {
            $suffixes = collect(self::BOTS)->firstWhere(0, $bot)[1] ?? [];
            $host = @gethostbyaddr($ip);
            if (! $host || $host === $ip) {
                return 0;
            }
            $host = strtolower($host);
            $matched = collect($suffixes)->contains(fn ($suffix) => str_ends_with($host, $suffix));

            return $matched && in_array(@inet_pton($ip), array_map(fn ($item) => @inet_pton($item), static::forward($host)), true) ? 1 : 0;
        });
    }

    /**
     * @return array<int, string>
     */
    protected static function forward(string $host): array
    {
        $ips = [];
        foreach ([DNS_A, DNS_AAAA] as $type) {
            foreach (@dns_get_record($host, $type) ?: [] as $record) {
                $ips[] = $record['ip'] ?? $record['ipv6'] ?? '';
            }
        }

        return array_filter($ips);
    }
}
