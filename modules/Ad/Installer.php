<?php

declare(strict_types=1);

namespace Modules\Ad;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Ad\Providers\AdServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '广告管理',
            'name' => 'ad',
            'path' => 'ad',
            'keywords' => '广告,广告位',
            'description' => '自有广告：图片或代码广告，按广告位和站点投放，统计点击',
            'provider' => AdServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
