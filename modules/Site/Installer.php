<?php

declare(strict_types=1);

namespace Modules\Site;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Site\Providers\SiteServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '站点管理',
            'name' => 'site',
            'path' => 'site',
            'keywords' => '站点管理,服务器,域名,Cloudflare',
            'description' => '站群的服务器、Cloudflare 账号、站点与域名接入',
            'provider' => SiteServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
