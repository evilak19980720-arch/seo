<article class="card">
    @if ($article->cover)
        <a href="/artigos/{{ $article->slug }}"><img src="{{ \Modules\Front\Support\CurrentSite::asset($article->cover) }}" alt="{{ $article->title }}" loading="lazy" width="640" height="360"></a>
    @endif
    <div class="body">
        <h3><a href="/artigos/{{ $article->slug }}">{{ $article->title }}</a></h3>
        <div class="muted">{{ \Illuminate\Support\Str::limit($article->meta_description ?: \Modules\Content\Support\Text::plain((string) $article->content), 120, '…') }}</div>
    </div>
</article>
