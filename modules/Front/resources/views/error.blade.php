@extends('front::layout', ['title' => ($status === 410 ? 'Conteúdo removido' : 'Página não encontrada').' – '.$current->site->name, 'description' => '', 'noindex' => true])

@section('content')
    <h1>{{ $status === 410 ? 'Este conteúdo foi removido' : 'Página não encontrada' }}</h1>
    <p class="muted">{{ $status === 410 ? 'O artigo que você procura não está mais disponível.' : 'O endereço que você acessou não existe.' }}</p>
    <p><a class="btn" href="/">Voltar ao início</a> <a class="btn ghost" href="/artigos">Ver artigos</a></p>
@endsection
