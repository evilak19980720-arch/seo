@php
    /** @var \Modules\Front\Support\CurrentSite $current */
    $site = $current->site;
    $pageTitle = $title ?? ($site->title ?: $site->name);
    $pageDescription = \Illuminate\Support\Str::limit($description ?? (string) $site->description, 160, '…');
    $canonical = $canonical ?? $current->url(request()->path());
    $image = $image ?? \Modules\Front\Support\CurrentSite::asset(($current->profile->screenshots ?? [])[0] ?? $site->logo);
    $logo = \Modules\Front\Support\CurrentSite::asset($site->logo);
    $schemas = array_values(array_filter($schemas ?? []));
@endphp
<!DOCTYPE html>
<html lang="{{ $site->locale ?: 'pt-BR' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @unless ($current->indexable() && empty($noindex))
        <meta name="robots" content="noindex, nofollow">
    @endunless
    <link rel="canonical" href="{{ $canonical }}">
    @if ($logo)
        <link rel="icon" href="{{ $logo }}">
        <link rel="apple-touch-icon" href="{{ $logo }}">
    @endif
    <meta property="og:site_name" content="{{ $site->name }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $site->locale ?: 'pt-BR') }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
    @stack('head')
    @foreach ($schemas as $schema)
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org'] + $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endforeach
    <style>
        :root { --bg: #ffffff; --fg: #1f2328; --muted: #59636e; --line: #e5e7eb; --brand: #1a56db; --soft: #f5f7fb; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0f1115; --fg: #e6e8eb; --muted: #9aa4af; --line: #262a31; --brand: #6ea8fe; --soft: #171a20; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--fg); font: 17px/1.7 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        a { color: var(--brand); }
        img { max-width: 100%; height: auto; }
        .wrap { max-width: 1080px; margin: 0 auto; padding: 0 16px; }
        header.top { border-bottom: 1px solid var(--line); }
        header.top .wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; min-height: 64px; }
        .brand { display: flex; align-items: center; gap: 10px; color: var(--fg); text-decoration: none; font-weight: 700; font-size: 1.1rem; }
        .brand img { width: 36px; height: 36px; border-radius: 8px; }
        header.top nav a { color: var(--fg); text-decoration: none; margin-left: 20px; }
        header.top nav a:hover { color: var(--brand); }
        main { padding: 32px 0 48px; }
        h1 { font-size: 2rem; line-height: 1.25; margin: 0 0 12px; }
        h2 { font-size: 1.45rem; line-height: 1.3; margin: 32px 0 12px; }
        h3 { font-size: 1.15rem; margin: 24px 0 8px; }
        .muted { color: var(--muted); }
        .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; background: var(--brand); color: #fff; text-decoration: none; font-weight: 600; }
        .btn.ghost { background: transparent; color: var(--brand); border: 1px solid var(--brand); }
        .grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
        .card { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: var(--bg); }
        .card img { display: block; width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
        .card .body { padding: 14px 16px 18px; }
        .card h3 { margin: 0 0 6px; font-size: 1.05rem; }
        .card h3 a { color: var(--fg); text-decoration: none; }
        .breadcrumb { font-size: .9rem; color: var(--muted); margin-bottom: 16px; }
        .breadcrumb a { color: var(--muted); }
        .prose { max-width: 760px; }
        .prose img { border-radius: 8px; }
        .prose table { border-collapse: collapse; width: 100%; }
        .prose td, .prose th { border: 1px solid var(--line); padding: 6px 10px; }
        .slot:empty { display: none; }
        .slot { margin: 24px 0; }
        footer.bottom { border-top: 1px solid var(--line); padding: 24px 0; font-size: .9rem; color: var(--muted); }
        @media (max-width: 640px) { h1 { font-size: 1.6rem; } header.top nav a { margin-left: 12px; } }
    </style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a class="brand" href="/">
            @if ($logo)<img src="{{ $logo }}" alt="{{ $site->name }}" width="36" height="36">@endif
            <span>{{ $site->name }}</span>
        </a>
        <nav>
            <a href="/">Início</a>
            <a href="/artigos">Artigos</a>
        </nav>
    </div>
</header>
@include('front::partials.slot', ['slot' => 'header'])
<main>
    <div class="wrap">
        @yield('content')
    </div>
</main>
@include('front::partials.slot', ['slot' => 'footer'])
<footer class="bottom">
    <div class="wrap">© {{ date('Y') }} {{ $site->name }}. Site oficial.</div>
</footer>
@stack('scripts')
</body>
</html>
