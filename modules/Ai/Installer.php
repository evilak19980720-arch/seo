<?php

declare(strict_types=1);

namespace Modules\Ai;

use Catch\Support\Module\Installer as ModuleInstaller;
use Modules\Ai\Providers\AiServiceProvider;

class Installer extends ModuleInstaller
{
    protected function info(): array
    {
        return [
            'title' => 'AI 管理',
            'name' => 'ai',
            'path' => 'ai',
            'keywords' => 'AI,提示词,渠道',
            'description' => 'AI 渠道、提示词与调用日志',
            'provider' => AiServiceProvider::class,
        ];
    }

    protected function requirePackages(): void
    {
    }

    protected function removePackages(): void
    {
    }
}
