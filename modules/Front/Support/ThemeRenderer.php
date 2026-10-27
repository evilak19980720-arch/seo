<?php

declare(strict_types=1);

namespace Modules\Front\Support;

/**
 * 把模板页头、页脚里的 [[占位符]] 换成站点内容（替换的值都由系统生成并转义）
 */
class ThemeRenderer
{
    public static function render(string $html, CurrentSite $current, string $footerText = ''): string
    {
        $site = $current->site;
        $logo = CurrentSite::asset($site->logo);

        return strtr($html, [
            '[[logo]]' => $logo ? sprintf('<img class="logo" src="%s" alt="%s" width="40" height="40">', e($logo), e($site->name)) : '',
            '[[site_name]]' => e($site->name),
            '[[nav]]' => static::nav(),
            '[[cta]]' => static::cta($current),
            '[[year]]' => date('Y'),
            '[[footer_text]]' => e(str_replace('{site}', $site->name, $footerText)),
        ]);
    }

    public static function nav(): string
    {
        return '<a href="/">Início</a> <a href="/artigos">Artigos</a>';
    }

    /**
     * 主按钮：点击会上报一次「按钮点击」
     */
    public static function cta(CurrentSite $current, string $class = 'btn cta'): string
    {
        $url = $current->ctaUrl();
        if ($url === '') {
            return '';
        }

        return sprintf('<a class="%s" href="%s" rel="noopener" target="_blank" data-track="cta">Jogar agora</a>', e($class), e($url));
    }
}
