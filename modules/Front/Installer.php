<?php

declare(strict_types=1);

namespace Modules\Front;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Front\Providers\FrontServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '前台站点',
            'name' => 'front',
            'path' => 'front',
            'keywords' => '前台,渲染,SEO,sitemap',
            'description' => '按访问域名渲染各游戏官网：页面、SEO 标签、结构化数据、sitemap、页面缓存',
            'provider' => FrontServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
