<?php

declare(strict_types=1);

namespace Modules\Front\Support;

use Catch\Enums\Status;
use Illuminate\Support\Facades\Cache;
use Modules\Site\Enums\OnboardStatus;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Modules\Site\Models\SiteTemplate;

/**
 * 当前请求对应的站点（由 ResolveSite 中间件按 Host 设置）
 */
class CurrentSite
{
    /** @var array{css: string, header: string, footer: string}|null|false false 表示还没读取 */
    protected array|null|false $theme;

    /**
     * @param  array{css: string, header: string, footer: string}|null|false  $theme  预览时传入草稿模板
     */
    public function __construct(
        public readonly Site $site,
        public readonly GameProfile $profile,
        array|null|false $theme = false
    ) {
        $this->theme = $theme;
    }

    /**
     * 站点启用的模板，没有时为 null（使用默认样式）
     *
     * @return array{css: string, header: string, footer: string}|null
     */
    public function theme(): ?array
    {
        if ($this->theme === false) {
            $this->theme = SiteTemplate::published($this->site->id);
        }

        return $this->theme ?: null;
    }

    /**
     * 主按钮（Jogar agora）跳转的商店地址，没有时为空
     */
    public function ctaUrl(): string
    {
        $links = array_filter($this->profile->store_links ?? []);

        return (string) ($links['google_play'] ?? $links['app_store'] ?? $links['web'] ?? '');
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
