<?php

declare(strict_types=1);

namespace Modules\Front\Providers;

use Catch\Providers\CatchModuleServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Content\Events\ArticlePublished;
use Modules\Content\Events\ArticleUnpublished;
use Modules\Front\Http\Controllers\PageController;
use Modules\Front\Http\Middleware\CachePage;
use Modules\Front\Http\Middleware\ResolveSite;
use Modules\Front\Jobs\PurgeEdgeCache;
use Modules\Front\Support\CurrentSite;
use Modules\Front\Support\PageCache;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FrontServiceProvider extends CatchModuleServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'front');

        $this->registerRoutes();
        $this->registerCacheInvalidation();
        $this->registerErrorPages();
    }

    /**
     * 前台路由不经过 web 中间件组：没有 Session 和 Cookie，页面可以被缓存
     */
    protected function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware([ResolveSite::class, CachePage::class])->group(function () {
            Route::get('/', [PageController::class, 'home']);
            Route::get('artigos', [PageController::class, 'articles']);
            Route::get('artigos/{slug}', [PageController::class, 'article'])->where('slug', '[a-z0-9-]+');
            Route::get('sitemap.xml', [PageController::class, 'sitemap']);
            Route::get('robots.txt', [PageController::class, 'robots']);
            // 其他路径也经过站点中间件，才能显示站点自己的 404 页面
            Route::fallback(fn () => abort(404));
        });
    }

    /**
     * 内容变化时让本机页面缓存失效，并清除 Cloudflare 上相关页面
     */
    protected function registerCacheInvalidation(): void
    {
        $articleChanged = function (ArticlePublished|ArticleUnpublished $event) {
            $article = $event->article;
            PageCache::flush($article->site_id);
            PurgeEdgeCache::dispatch($article->site_id, ['/', 'artigos', 'artigos/'.$article->slug, 'sitemap.xml']);
        };
        Event::listen(ArticlePublished::class, $articleChanged);
        Event::listen(ArticleUnpublished::class, $articleChanged);

        Site::saved(function (Site $site) {
            CurrentSite::forgetHost($site->domain);
            PageCache::flush($site->id);
        });
        Site::deleted(fn (Site $site) => CurrentSite::forgetHost($site->domain));

        GameProfile::saved(function (GameProfile $profile) {
            PageCache::flush($profile->site_id);
            PurgeEdgeCache::dispatch($profile->site_id, ['/', 'artigos', 'sitemap.xml']);
        });
    }

    /**
     * 站点域名下的 404 / 410 使用站点自己的错误页
     */
    protected function registerErrorPages(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);
        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (HttpExceptionInterface $e) {
            if (! $this->app->bound(CurrentSite::class) || ! in_array($e->getStatusCode(), [404, 410], true)) {
                return null;
            }

            return response()->view('front::error', ['current' => $this->app->make(CurrentSite::class), 'status' => $e->getStatusCode()], $e->getStatusCode())
                ->header('X-Robots-Tag', 'noindex');
        });
    }

    /**
     * 前台路由在 boot 里注册，不使用后台的 api 路由文件
     */
    protected function loadModuleRoute(): void
    {
    }

    public function moduleName(): string
    {
        return 'front';
    }
}
