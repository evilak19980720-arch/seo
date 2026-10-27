<?php

declare(strict_types=1);

namespace Modules\Content;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Content\Providers\ContentServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => '内容管理',
            'name' => 'content',
            'path' => 'content',
            'keywords' => '文章,选题,审核,发布',
            'description' => '选题、AI 生成与润色、AI 审核、发布队列',
            'provider' => ContentServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
