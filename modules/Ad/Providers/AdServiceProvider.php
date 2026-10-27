<?php

declare(strict_types=1);

namespace Modules\Ad\Providers;

use Catch\Providers\CatchModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Ad\Http\Controllers\ClickController;
use Modules\Ad\Support\AdRenderer;
use Modules\Front\Http\Middleware\ResolveSite;

class AdServiceProvider extends CatchModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        // 前台模板的广告位通过 front.slot 取 HTML
        $this->app->singleton('front.slot', fn () => new AdRenderer());
    }

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            // 广告点击跳转（不缓存、不收录）
            Route::middleware([ResolveSite::class])->get('go/{id}', ClickController::class)->whereNumber('id');
        }
    }

    public function moduleName(): string
    {
        return 'ad';
    }
}
