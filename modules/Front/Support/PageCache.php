<?php

declare(strict_types=1);

namespace Modules\Front\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 页面 HTML 缓存。每个站点一个版本号，内容变化时换版本号，旧缓存自然过期
 */
class PageCache
{
    public static function key(int $siteId, string $path, int $page): string
    {
        return sprintf('front:page:%d:%s:%s:%d', $siteId, static::version($siteId), md5($path), $page);
    }

    public static function version(int $siteId): string
    {
        return (string) Cache::rememberForever('front:ver:'.$siteId, fn () => Str::random(8));
    }

    public static function flush(int $siteId): void
    {
        Cache::forever('front:ver:'.$siteId, Str::random(8));
    }
}
