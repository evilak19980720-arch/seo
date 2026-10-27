<?php

declare(strict_types=1);

namespace Modules\Ai\Providers;

use Catch\Providers\CatchModuleServiceProvider;

class AiServiceProvider extends CatchModuleServiceProvider
{
    /**
     * route path
     */
    public function moduleName(): string
    {
        return 'ai';
    }
}
