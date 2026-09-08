@php
    /** @var \IProfile\Seo\Data\SeoData $seo */
    $seo = $seo ?? \IProfile\Seo\Facades\Seo::data();

    $hreflangMap = config('seo.hreflang.map', []);
    $image = $seo->resolvedImage();
    $imageSize = config('seo.og_image_size');
@endphp
<title>{{ $seo->fullTitle() }}</title>
@if($seo->resolvedDescription())
<meta name="description" content="{{ $seo->resolvedDescription() }}">
@endif
<meta name="robots" content="{{ $seo->resolvedRobots() }}">
<link rel="canonical" href="{{ $seo->resolvedCanonical() }}">

@if(config('seo.hreflang.enabled', true) && count($seo->alternates) > 1)
@foreach($seo->alternates as $locale => $url)
<link rel="alternate" hreflang="{{ $hreflangMap[$locale] ?? $locale }}" href="{{ $url }}">
@endforeach
@if($default = ($seo->alternates[config('seo.hreflang.x_default')] ?? null))
<link rel="alternate" hreflang="x-default" href="{{ $default }}">
@endif
@endif

<meta property="og:type" content="{{ $seo->type }}">
<meta property="og:site_name" content="{{ $seo->siteName() }}">
<meta property="og:locale" content="{{ $seo->openGraphLocale() }}">
<meta property="og:title" content="{{ $seo->resolvedTitle() }}">
@if($seo->resolvedDescription())
<meta property="og:description" content="{{ $seo->resolvedDescription() }}">
@endif
<meta property="og:url" content="{{ $seo->resolvedCanonical() }}">
@if($image)
<meta property="og:image" content="{{ $image }}">
@if($imageSize)
<meta property="og:image:width" content="{{ $imageSize['width'] }}">
<meta property="og:image:height" content="{{ $imageSize['height'] }}">
@endif
<meta property="og:image:alt" content="{{ $seo->resolvedTitle() }}">
@endif

@if($seo->type === 'article')
@if($published = $seo->publishedAtIso())<meta property="article:published_time" content="{{ $published }}">@endif
@if($modified = $seo->updatedAtIso())<meta property="article:modified_time" content="{{ $modified }}">@endif
@if($seo->author)<meta property="article:author" content="{{ $seo->author }}">@endif
@endif

<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
@if(config('seo.twitter_handle'))
<meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
@endif
<meta name="twitter:title" content="{{ $seo->resolvedTitle() }}">
@if($seo->resolvedDescription())
<meta name="twitter:description" content="{{ $seo->resolvedDescription() }}">
@endif
@if($image)
<meta name="twitter:image" content="{{ $image }}">
@endif

@if(config('seo.organization.enabled', true))
<x-seo::schema.organization />
@endif
@if($seo->breadcrumbs)
<x-seo::schema.breadcrumbs :items="$seo->breadcrumbs" />
@endif
@foreach($seo->schemas as $schema)
<x-seo::schema.json :data="$schema" />
@endforeach
