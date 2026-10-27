<?php

declare(strict_types=1);

namespace Modules\Front\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Front\Support\CurrentSite;
use Modules\Front\Support\PageCache;
use Symfony\Component\HttpFoundation\Response;

/**
 * 缓存成功的 GET 页面；只认 page 参数，其他参数不影响缓存
 */
class CachePage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodCacheable()) {
            return $next($request);
        }

        $current = app(CurrentSite::class);
        $key = PageCache::key($current->site->id, '/'.ltrim($request->path(), '/'), max(1, (int) $request->query('page', 1)));

        if ($cached = Cache::get($key)) {
            return $this->headers(response($cached['body'], 200, ['Content-Type' => $cached['type']]), $current, 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && ! $response->headers->has('Set-Cookie')) {
            Cache::put($key, ['body' => $response->getContent(), 'type' => $response->headers->get('Content-Type')], config('front.page_ttl'));
        }

        return $this->headers($response, $current, 'MISS');
    }

    protected function headers(Response $response, CurrentSite $current, string $state): Response
    {
        $response->headers->set('X-Page-Cache', $state);
        if ($response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage='.config('front.edge_ttl'));
        }
        if (! $current->indexable()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
