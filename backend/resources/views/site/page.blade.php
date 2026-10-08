@extends('site.layout')

@section('content')
    <article class="reading">
        <header class="container-reading article-header">
            <h1 class="article-title">{{ __("site.pages.{$page}") }}</h1>
            @if ($updated)
                <p class="byline"><span>{{ __('site.pages.updated', ['date' => \Illuminate\Support\Carbon::parse($updated)->locale($locale)->isoFormat('LL')]) }}</span></p>
            @endif
        </header>
        <div class="container-reading prose">
            @include($content)
        </div>
    </article>
@endsection
