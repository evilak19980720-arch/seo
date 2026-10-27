<?php

declare(strict_types=1);

namespace Modules\Site\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * 清理 AI 生成的模板：只保留静态 HTML 标签和安全的 CSS，去掉脚本、事件、外部资源
 */
class TemplateSanitizer
{
    protected const TAGS = [
        'header', 'footer', 'nav', 'div', 'span', 'a', 'img', 'p', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'small',
        'h1', 'h2', 'h3', 'h4', 'section', 'aside', 'br', 'hr',
    ];

    protected const ATTRIBUTES = ['class', 'id', 'href', 'src', 'alt', 'title', 'role', 'width', 'height', 'style', 'aria-label', 'aria-hidden'];

    public const MAX_CSS = 80000;

    public const MAX_HTML = 20000;

    public static function html(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.mb_substr($html, 0, self::MAX_HTML).'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $root = $dom->getElementById('__root');
        if (! $root) {
            return '';
        }
        static::clean($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    protected static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (! in_array($tag, self::TAGS, true)) {
                // 未允许的标签整个删除（script、style、iframe、form 等）
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                $keep = in_array($name, self::ATTRIBUTES, true) && match ($name) {
                    'href' => static::safeUrl($value, false),
                    'src' => static::safeUrl($value, true),
                    'style' => ($attribute->value = static::css($value)) !== '',
                    default => true,
                };
                if (! $keep) {
                    $child->removeAttribute($attribute->name);
                }
            }

            static::clean($child);
        }
    }

    /**
     * 只允许站内相对链接、锚点和 https 图片；占位符 [[...]] 原样保留
     */
    protected static function safeUrl(string $url, bool $image): bool
    {
        if ($url === '' || str_starts_with($url, '[[')) {
            return true;
        }
        if ($image) {
            return (bool) preg_match('#^(https://|/(?!/)|data:image/(png|jpe?g|gif|webp);base64,)#i', $url);
        }

        return (bool) preg_match('#^(/(?!/)|\#)#', $url);
    }

    public static function css(?string $css): string
    {
        $css = mb_substr((string) $css, 0, self::MAX_CSS);
        // 去掉所有 <，避免跳出 <style> 标签（data: SVG 需要写成 %3C）
        $css = str_replace(['<', '-->'], '', $css);
        $css = preg_replace('/@import[^;]*;?/i', '', $css) ?? '';
        $css = preg_replace('/(expression\s*\(|javascript\s*:|vbscript\s*:|behavior\s*:|-moz-binding)/i', '', $css) ?? '';
        // url() 只保留 data:image，外部资源全部去掉
        $css = preg_replace('/url\(\s*(?![\'"]?\s*data:image\/)[^)]*\)/i', 'none', $css) ?? '';

        return trim($css);
    }
}
