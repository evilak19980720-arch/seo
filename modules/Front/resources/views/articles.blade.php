@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $articles */
    $site = $current->site;
    $page = $articles->currentPage();
    $canonical = $current->url($page > 1 ? 'artigos?page='.$page : 'artigos');
    $schemas = [[
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $current->url()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Artigos', 'item' => $current->url('artigos')],
        ],
    ]];
@endphp
@extends('front::layout', [
    'title' => 'Artigos e guias – '.$site->name.($page > 1 ? ' – página '.$page : ''),
    'description' => 'Guias, dicas e novidades oficiais de '.$site->name.'.',
    'canonical' => $canonical,
    'schemas' => $schemas,
])

@push('head')
    @if ($articles->previousPageUrl())<link rel="prev" href="{{ $current->url(ltrim($articles->previousPageUrl(), '/')) }}">@endif
    @if ($articles->nextPageUrl())<link rel="next" href="{{ $current->url(ltrim($articles->nextPageUrl(), '/')) }}">@endif
@endpush

@section('content')
    <nav class="breadcrumb"><a href="/">Início</a> › Artigos</nav>
    <h1>Artigos e guias</h1>
    @if ($articles->isEmpty())
        <p class="muted">Em breve publicaremos guias e novidades de {{ $site->name }}.</p>
    @else
        <div class="grid">
            @foreach ($articles as $article)
                @include('front::partials.card', ['article' => $article])
            @endforeach
        </div>
        @if ($articles->hasPages())
            <p style="display:flex;justify-content:space-between;margin-top:28px">
                <span>@if ($articles->previousPageUrl())<a href="{{ $articles->previousPageUrl() }}">← Anteriores</a>@endif</span>
                <span class="muted">Página {{ $page }} de {{ $articles->lastPage() }}</span>
                <span>@if ($articles->nextPageUrl())<a href="{{ $articles->nextPageUrl() }}">Próximos →</a>@endif</span>
            </p>
        @endif
    @endif
@endsection
