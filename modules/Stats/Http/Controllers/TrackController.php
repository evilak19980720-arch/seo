<?php

declare(strict_types=1);

namespace Modules\Stats\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Front\Support\CurrentSite;
use Modules\Stats\Support\Stats;

/**
 * 前台页面上报：pv（访问量）、cta（按钮点击）
 */
class TrackController
{
    protected const EVENTS = ['pv' => Stats::PAGEVIEWS, 'cta' => Stats::CLICKS];

    /** 同一 IP 每分钟最多记录的次数，防止刷数据 */
    protected const PER_MINUTE = 60;

    public function __invoke(Request $request, CurrentSite $current): Response
    {
        $metric = self::EVENTS[(string) $request->input('e')] ?? null;
        $ua = strtolower((string) $request->userAgent());

        if ($metric && $ua !== '' && ! preg_match('/bot|spider|crawl|slurp|headless|preview|fetch/', $ua)) {
            $ip = (string) ($request->header('CF-Connecting-IP') ?: $request->ip());
            $key = 'stats:track:'.md5($ip).':'.date('YmdHi');
            Cache::add($key, 0, 120);
            if (Cache::increment($key) <= self::PER_MINUTE) {
                Stats::add($current->site->id, $metric);
            }
        }

        return response('', 204)->header('Cache-Control', 'no-store');
    }
}
