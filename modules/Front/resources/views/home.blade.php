@php
    use Modules\Front\Support\CurrentSite;
    $site = $current->site;
    $profile = $current->profile;
    $screenshots = array_map([CurrentSite::class, 'asset'], $profile->screenshots ?? []);
    $stores = array_filter($profile->store_links ?? []);
    $storeLabels = ['google_play' => 'Baixar no Google Play', 'app_store' => 'Baixar na App Store', 'web' => 'Jogar no navegador'];
    $youtube = preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', (string) $profile->video_url, $m) ? $m[1] : null;
    $platforms = array_values(array_intersect_key(['google_play' => 'Android', 'app_store' => 'iOS', 'web' => 'Web browser'], $stores));
    $schemas = [
        ['@type' => 'WebSite', 'name' => $site->name, 'url' => $current->url(), 'inLanguage' => $site->locale],
        array_filter([
            '@type' => 'VideoGame',
            'name' => $site->name,
            'url' => $current->url(),
            'description' => $profile->summary ?: $site->description,
            'genre' => $profile->genre ?: null,
            'image' => $screenshots ?: null,
            'gamePlatform' => $platforms ?: null,
            'inLanguage' => $site->locale,
            'sameAs' => array_values($stores) ?: null,
        ]),
    ];
@endphp
@extends('front::layout', ['title' => $site->title ?: $site->name, 'description' => $site->description ?: $profile->summary, 'canonical' => $current->url(), 'schemas' => $schemas])

@section('content')
    <section>
        <h1>{{ $site->title ?: $site->name }}</h1>
        @if ($profile->genre)<p class="muted">{{ $profile->genre }}</p>@endif
        @if ($profile->summary)<p style="font-size:1.15rem">{{ $profile->summary }}</p>@endif
        @if ($stores)
            <p style="display:flex;flex-wrap:wrap;gap:12px">
                @foreach ($stores as $key => $url)
                    <a class="btn{{ $loop->first ? '' : ' ghost' }}" href="{{ $url }}" rel="noopener" target="_blank">{{ $storeLabels[$key] ?? $url }}</a>
                @endforeach
            </p>
        @endif
    </section>

    @if ($screenshots)
        <section>
            <h2>Imagens do jogo</h2>
            <div class="grid">
                @foreach ($screenshots as $src)
                    <img src="{{ $src }}" alt="{{ $site->name }} – imagem {{ $loop->iteration }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" style="border-radius:10px">
                @endforeach
            </div>
        </section>
    @endif

    @include('front::partials.slot', ['slot' => 'home_middle'])

    @if ($youtube)
        <section>
            <h2>Trailer</h2>
            <div style="position:relative;aspect-ratio:16/9">
                <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtube }}" title="Trailer de {{ $site->name }}" loading="lazy" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:10px"></iframe>
            </div>
        </section>
    @endif

    @if ($profile->gameplay)
        <section class="prose">
            <h2>Como jogar</h2>
            {!! \Illuminate\Support\Str::markdown($profile->gameplay, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </section>
    @endif

    @if ($profile->features)
        <section class="prose">
            <h2>Destaques</h2>
            <ul>
                @foreach ($profile->features as $feature)<li>{{ $feature }}</li>@endforeach
            </ul>
        </section>
    @endif

    @if ($articles->isNotEmpty())
        <section>
            <h2>Últimos artigos</h2>
            <div class="grid">
                @foreach ($articles as $article)
                    @include('front::partials.card', ['article' => $article])
                @endforeach
            </div>
            <p><a href="/artigos">Ver todos os artigos →</a></p>
        </section>
    @endif

    @if ($profile->faq)
        <section class="prose">
            <h2>Perguntas frequentes</h2>
            @foreach ($profile->faq as $item)
                <details style="border-bottom:1px solid var(--line);padding:10px 0">
                    <summary style="cursor:pointer;font-weight:600">{{ $item['q'] }}</summary>
                    <p>{{ $item['a'] }}</p>
                </details>
            @endforeach
        </section>
    @endif
@endsection
