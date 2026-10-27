<?php

declare(strict_types=1);

namespace Modules\Site\Providers;

use Catch\Providers\CatchModuleServiceProvider;

class SiteServiceProvider extends CatchModuleServiceProvider
{
    /**
     * route path
     */
    public function moduleName(): string
    {
        return 'site';
    }
}
