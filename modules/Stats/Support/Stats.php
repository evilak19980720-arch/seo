<?php

declare(strict_types=1);

namespace Modules\Stats\Support;

use Illuminate\Support\Facades\DB;

/**
 * 每日统计计数
 */
class Stats
{
    public const CRAWL_GOOGLE = 'crawl_google';

    public const CRAWL_BING = 'crawl_bing';

    public const CF_REQUESTS = 'cf_requests';

    public const CF_PAGEVIEWS = 'cf_pageviews';

    public const CF_UNIQUES = 'cf_uniques';

    public const AD_CLICKS = 'ad_clicks';

    /**
     * @return array<string, string>
     */
    public static function metrics(): array
    {
        return [
            self::CRAWL_GOOGLE => 'Googlebot 抓取',
            self::CRAWL_BING => 'Bingbot 抓取',
            self::CF_PAGEVIEWS => '页面浏览（Cloudflare）',
            self::CF_UNIQUES => '独立访客（Cloudflare）',
            self::CF_REQUESTS => '请求数（Cloudflare）',
            self::AD_CLICKS => '广告点击',
        ];
    }

    public static function add(int $siteId, string $metric, int $value = 1, ?int $date = null): void
    {
        $where = ['date' => $date ?? (int) date('Ymd'), 'site_id' => $siteId, 'metric' => $metric];

        if (! DB::table('stats_daily')->where($where)->increment('value', $value)) {
            DB::table('stats_daily')->insertOrIgnore($where + ['value' => 0]);
            DB::table('stats_daily')->where($where)->increment('value', $value);
        }
    }

    public static function set(int $siteId, string $metric, int $value, int $date): void
    {
        DB::table('stats_daily')->updateOrInsert(['date' => $date, 'site_id' => $siteId, 'metric' => $metric], ['value' => $value]);
    }
}
