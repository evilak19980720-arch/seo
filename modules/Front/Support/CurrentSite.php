<?php

declare(strict_types=1);

namespace Modules\Front\Support;

use Catch\Enums\Status;
use Illuminate\Support\Facades\Cache;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;

/**
 * 当前请求对应的站点（由 ResolveSite 中间件按 Host 设置）
 */
class CurrentSite
{
    public function __construct(
        public readonly Site $site,
        public readonly GameProfile $profile
    ) {
    }

    /**
     * 按域名查找已启用的站点 ID，结果缓存 10 分钟
     */
    public static function idForHost(string $host): int
    {
        return (int) Cache::remember('front:host:'.$host, 600, fn () => (int) Site::query()
            ->where('domain', $host)
            ->where('status', Status::Enable->value)
            ->value('id'));
    }

    public static function forgetHost(string $host): void
    {
        Cache::forget('front:host:'.$host);
    }

    /**
     * 游戏资料未确认或域名未上线时，页面不让搜索引擎收录
     */
    public function indexable(): bool
    {
        return $this->profile->isConfirmed() && $this->site->onboard_status === OnboardStatus::Online->value;
    }

    public function url(string $path = '/'): string
    {
        return 'https://'.$this->site->domain.'/'.ltrim($path, '/');
    }

    /**
     * 后台上传的文件转成完整 URL
     */
    public static function asset(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return rtrim(config('front.asset_url') ?: config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
