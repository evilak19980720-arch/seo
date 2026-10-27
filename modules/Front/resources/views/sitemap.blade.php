{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@if ($current->indexable())
    <url><loc>{{ $current->url() }}</loc>@if ($articles->isNotEmpty())<lastmod>{{ date('Y-m-d', $articles->max('published_at')) }}</lastmod>@endif</url>
    <url><loc>{{ $current->url('artigos') }}</loc></url>
@foreach ($articles as $article)
    <url><loc>{{ $current->url('artigos/'.$article->slug) }}</loc><lastmod>{{ date('Y-m-d', max((int) $article->getRawOriginal('updated_at'), $article->published_at)) }}</lastmod></url>
@endforeach
@endif
</urlset>
