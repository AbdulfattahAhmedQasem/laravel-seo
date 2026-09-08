@php
    /** @var \IProfile\Seo\Data\SeoData $seo */
    $seo = $seo ?? \IProfile\Seo\Facades\Seo::data();
    $hreflang = config('seo.hreflang.map', []);
@endphp
<title>{{ $seo->fullTitle() }}</title>
@if($seo->resolvedDescription())
<meta name="description" content="{{ $seo->resolvedDescription() }}">
@endif
<meta name="robots" content="{{ $seo->resolvedRobots() }}">
<link rel="canonical" href="{{ $seo->resolvedCanonical() }}">

@if(config('seo.hreflang.enabled', true) && $seo->alternates)
@foreach($seo->alternates as $locale => $url)
<link rel="alternate" hreflang="{{ $hreflang[$locale] ?? $locale }}" href="{{ $url }}">
@endforeach
@if($default = ($seo->alternates[config('seo.hreflang.x_default')] ?? null))
<link rel="alternate" hreflang="x-default" href="{{ $default }}">
@endif
@endif

<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:site_name" content="{{ config('seo.title_suffix') }}">
<meta property="og:locale" content="{{ str_replace('-', '_', $hreflang[app()->getLocale()] ?? app()->getLocale()) }}">
<meta property="og:title" content="{{ $seo->title ?: $seo->fullTitle() }}">
@if($seo->resolvedDescription())
<meta property="og:description" content="{{ $seo->resolvedDescription() }}">
@endif
<meta property="og:url" content="{{ $seo->resolvedCanonical() }}">
@if($seo->resolvedImage())
<meta property="og:image" content="{{ $seo->resolvedImage() }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $seo->title }}">
@endif

@if($seo->type === 'article')
@if($seo->publishedAt)<meta property="article:published_time" content="{{ \Illuminate\Support\Carbon::parse($seo->publishedAt)->toIso8601String() }}">@endif
@if($seo->updatedAt)<meta property="article:modified_time" content="{{ \Illuminate\Support\Carbon::parse($seo->updatedAt)->toIso8601String() }}">@endif
@if($seo->author)<meta property="article:author" content="{{ $seo->author }}">@endif
@endif

<meta name="twitter:card" content="{{ $seo->resolvedImage() ? 'summary_large_image' : 'summary' }}">
@if(config('seo.twitter_handle'))
<meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
@endif
<meta name="twitter:title" content="{{ $seo->title ?: $seo->fullTitle() }}">
@if($seo->resolvedDescription())
<meta name="twitter:description" content="{{ $seo->resolvedDescription() }}">
@endif
@if($seo->resolvedImage())
<meta name="twitter:image" content="{{ $seo->resolvedImage() }}">
@endif

@if(config('seo.organization.enabled', true))
<x-seo::schema.organization />
@endif
@if($seo->breadcrumbs)
<x-seo::schema.breadcrumbs :items="$seo->breadcrumbs" />
@endif
@foreach($seo->schemas as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
