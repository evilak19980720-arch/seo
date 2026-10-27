<?php

declare(strict_types=1);

namespace Modules\Front\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Content\Enums\ArticleStatus;
use Modules\Content\Models\Article;
use Modules\Front\Support\CurrentSite;

class PageController
{
    public function __construct(
        protected readonly CurrentSite $current
    ) {
    }

    public function home(): Response
    {
        return response()->view('front::home', [
            'current' => $this->current,
            'articles' => $this->published()->limit(6)->get(),
        ]);
    }

    public function articles(Request $request): Response
    {
        $articles = $this->published()->paginate(config('front.per_page'), ['*'], 'page')->withPath('/artigos');
        if ($articles->currentPage() > max(1, $articles->lastPage())) {
            abort(404);
        }

        return response()->view('front::articles', ['current' => $this->current, 'articles' => $articles]);
    }

    public function article(string $slug): Response
    {
        $article = Article::query()->where('site_id', $this->current->site->id)->where('slug', $slug)->first();

        if (! $article || $article->status !== ArticleStatus::Published->value) {
            // 下线或删除的文章返回 410，让搜索引擎尽快移除
            $gone = $article?->status === ArticleStatus::Offline->value
                || Article::withTrashed()->where('site_id', $this->current->site->id)->where('slug', $slug)->exists();
            abort($gone ? 410 : 404);
        }

        return response()->view('front::article', [
            'current' => $this->current,
            'article' => $article,
            'html' => Str::markdown((string) $article->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'related' => $this->published()->whereKeyNot($article->id)->limit(3)->get(),
        ]);
    }

    public function sitemap(): Response
    {
        $articles = $this->current->indexable() ? $this->published()->get(['slug', 'published_at', 'updated_at']) : collect();

        return response()->view('front::sitemap', ['current' => $this->current, 'articles' => $articles])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /', 'Disallow: /go/', '', 'Sitemap: '.$this->current->url('sitemap.xml')];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    protected function published()
    {
        return Article::query()
            ->where('site_id', $this->current->site->id)
            ->where('status', ArticleStatus::Published->value)
            ->orderByDesc('published_at')
            ->select(['id', 'site_id', 'title', 'slug', 'meta_title', 'meta_description', 'cover', 'content', 'word_count', 'published_at', 'updated_at']);
    }
}
