@php
    use Modules\Front\Support\CurrentSite;
    $site = $current->site;
    $url = $current->url('artigos/'.$article->slug);
    $cover = CurrentSite::asset($article->cover);
    $modified = (int) $article->getRawOriginal('updated_at') ?: $article->published_at;
    $publisher = array_filter(['@type' => 'Organization', 'name' => $site->name, 'url' => $current->url(), 'logo' => CurrentSite::asset($site->logo) ?: null]);
    $schemas = [
        array_filter([
            '@type' => 'Article',
            'headline' => \Illuminate\Support\Str::limit($article->title, 110, ''),
            'description' => $article->meta_description ?: null,
            'image' => $cover ? [$cover] : null,
            'datePublished' => date(DATE_ATOM, $article->published_at),
            'dateModified' => date(DATE_ATOM, max($modified, $article->published_at)),
            'inLanguage' => $site->locale,
            'mainEntityOfPage' => $url,
            'author' => $publisher,
            'publisher' => $publisher,
        ]),
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $current->url()],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artigos', 'item' => $current->url('artigos')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $url],
            ],
        ],
    ];
@endphp
@extends('front::layout', [
    'title' => $article->meta_title ?: $article->title.' – '.$site->name,
    'description' => $article->meta_description ?: \Modules\Content\Support\Text::plain((string) $article->content),
    'canonical' => $url,
    'ogType' => 'article',
    'image' => $cover ?: null,
    'schemas' => $schemas,
])

@push('head')
    <meta property="article:published_time" content="{{ date(DATE_ATOM, $article->published_at) }}">
@endpush

@section('content')
    <nav class="breadcrumb"><a href="/">Início</a> › <a href="/artigos">Artigos</a> › {{ $article->title }}</nav>
    <article class="prose">
        <h1>{{ $article->title }}</h1>
        <p class="muted">Publicado em <time datetime="{{ date('Y-m-d', $article->published_at) }}">{{ date('d/m/Y', $article->published_at) }}</time> · Equipe {{ $site->name }}</p>
        @if ($cover)
            <img src="{{ $cover }}" alt="{{ $article->title }}" width="1280" height="720" style="aspect-ratio:16/9;object-fit:cover;width:100%">
        @endif
        @include('front::partials.slot', ['slot' => 'article_top', 'context' => ['article' => $article]])
        {!! $html !!}
        @include('front::partials.slot', ['slot' => 'article_bottom', 'context' => ['article' => $article]])
    </article>

    @if ($related->isNotEmpty())
        <section>
            <h2>Leia também</h2>
            <div class="grid">
                @foreach ($related as $item)
                    @include('front::partials.card', ['article' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
