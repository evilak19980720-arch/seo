<?php

declare(strict_types=1);

namespace Modules\Stats;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Stats\Providers\StatsServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '数据统计',
            'name' => 'stats',
            'path' => 'stats',
            'keywords' => '统计,蜘蛛,流量',
            'description' => '蜘蛛抓取（反向 DNS 验证）、Cloudflare 流量、广告点击、内容产出',
            'provider' => StatsServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
