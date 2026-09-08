@props(['searchUrl' => null])
@php
    $home = rtrim(url('/'), '/');

    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'WebSite',
        '@id'        => $home.'/#website',
        'url'        => $home.'/',
        'name'       => config('seo.organization.name') ?: config('app.name'),
        'publisher'  => ['@id' => $home.'/#organization'],
        'inLanguage' => app()->getLocale(),
        'potentialAction' => $searchUrl ? [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $searchUrl.'{search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ] : null,
    ];
@endphp
<x-seo::schema.json :data="$data" />
