<?php

declare(strict_types=1);

namespace Modules\Front\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Front\Support\CurrentSite;
use Modules\Site\Models\GameProfile;
use Modules\Site\Models\Site;
use Symfony\Component\HttpFoundation\Response;

/**
 * 按访问域名找到站点；www 跳转到根域名；未知域名返回 404
 */
class ResolveSite
{
    public function handle(Request $request, Closure $next): Response
    {
        // 常驻进程（Octane、队列里渲染）中不能沿用上一个请求的站点
        app()->forgetInstance(CurrentSite::class);
        $host = strtolower($request->getHost());

        if (! $id = CurrentSite::idForHost($host)) {
            if (str_starts_with($host, 'www.') && CurrentSite::idForHost(substr($host, 4))) {
                return redirect()->away('https://'.substr($host, 4).$request->getRequestUri(), 301);
            }

            // 后台域名访问根路径时显示默认页面
            if ($request->path() === '/' && $host === parse_url((string) config('app.url'), PHP_URL_HOST)) {
                return response()->view('welcome');
            }

            abort(404);
        }

        $site = Site::query()->findOrFail($id);
        app()->instance(CurrentSite::class, new CurrentSite($site, GameProfile::forSite($site->id)));

        return $next($request);
    }
}
