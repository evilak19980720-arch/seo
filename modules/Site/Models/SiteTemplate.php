<?php

declare(strict_types=1);

namespace Modules\Site\Models;

use Catch\Base\CatchModel as Model;
use Catch\Exceptions\FailedException;
use Modules\Front\Jobs\PurgeEdgeCache;
use Modules\Front\Support\PageCache;
use Modules\Site\Jobs\GenerateSiteTemplate;
use Modules\Site\Support\TemplateSanitizer;

/**
 * 站点模板：AI 按参考图生成草稿，人工预览确认后启用
 *
 * @property int $id
 * @property int $site_id
 * @property string $reference_image
 * @property string $style_notes
 * @property string|null $css
 * @property string|null $header_html
 * @property string|null $footer_html
 * @property string|null $published_css
 * @property string|null $published_header
 * @property string|null $published_footer
 * @property int $status
 */
class SiteTemplate extends Model
{
    public const NONE = 0;

    public const GENERATING = 1;

    public const DRAFT = 2;

    public const PUBLISHED = 3;

    public const FAILED = 4;

    protected $table = 'site_templates';

    protected $fillable = [
        'id', 'site_id', 'reference_image', 'style_notes', 'css', 'header_html', 'footer_html',
        'published_css', 'published_header', 'published_footer', 'status', 'error', 'published_at',
        'creator_id', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'status' => 'integer',
        'published_at' => 'integer',
    ];

    public static function forSite(int $siteId): self
    {
        return static::query()->firstOrCreate(['site_id' => $siteId], ['status' => self::NONE, 'created_at' => time(), 'updated_at' => time()]);
    }

    /**
     * 已启用的模板，没有则返回 null（使用默认模板）
     *
     * @return array{css: string, header: string, footer: string}|null
     */
    public static function published(int $siteId): ?array
    {
        $template = static::query()->where('site_id', $siteId)->first(['published_css', 'published_header', 'published_footer']);
        if (! $template || ! $template->published_css) {
            return null;
        }

        return ['css' => (string) $template->published_css, 'header' => (string) $template->published_header, 'footer' => (string) $template->published_footer];
    }

    /**
     * @return array{css: string, header: string, footer: string}
     */
    public function draft(): array
    {
        return ['css' => (string) $this->css, 'header' => (string) $this->header_html, 'footer' => (string) $this->footer_html];
    }

    /**
     * 保存参考图、风格说明，或人工修改的草稿（保存前清理）
     */
    public function saveForSite(int $siteId, array $data): bool
    {
        $template = static::forSite($siteId);
        foreach (['css' => 'css', 'header_html' => 'html', 'footer_html' => 'html'] as $field => $type) {
            if (array_key_exists($field, $data)) {
                $data[$field] = TemplateSanitizer::{$type}($data[$field]);
            }
        }
        if (isset($data['css']) && $template->status === self::NONE) {
            $data['status'] = self::DRAFT;
        }

        return $template->update($data);
    }

    public function generate(int $siteId): bool
    {
        $template = static::forSite($siteId);
        if ($template->status === self::GENERATING) {
            throw new FailedException('模板正在生成中');
        }
        $template->update(['status' => self::GENERATING, 'error' => '']);
        GenerateSiteTemplate::dispatch($siteId);

        return true;
    }

    /**
     * 确认草稿并启用到前台
     */
    public function publish(int $siteId): bool
    {
        $template = static::forSite($siteId);
        if (trim((string) $template->css) === '') {
            throw new FailedException('还没有可以启用的模板');
        }

        $updated = $template->update([
            'published_css' => $template->css,
            'published_header' => $template->header_html,
            'published_footer' => $template->footer_html,
            'status' => self::PUBLISHED,
            'published_at' => time(),
        ]);
        static::refreshFront($siteId);

        return $updated;
    }

    /**
     * 停用模板，恢复默认模板
     */
    public function unpublish(int $siteId): bool
    {
        $updated = static::forSite($siteId)->update(['published_css' => null, 'published_header' => null, 'published_footer' => null, 'status' => self::DRAFT]);
        static::refreshFront($siteId);

        return $updated;
    }

    /**
     * 模板变化后刷新前台页面缓存（本机和 Cloudflare）
     */
    protected static function refreshFront(int $siteId): void
    {
        PageCache::flush($siteId);
        PurgeEdgeCache::dispatch($siteId, ['/', 'artigos']);
    }
}
