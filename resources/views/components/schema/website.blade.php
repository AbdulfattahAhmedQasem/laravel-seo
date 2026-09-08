@props(['searchUrl' => null])
@php
    $data = array_filter([
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        '@id'      => rtrim(url('/'), '/').'/#website',
        'url'      => url('/'),
        'name'     => config('seo.organization.name'),
        'publisher' => ['@id' => rtrim(url('/'), '/').'/#organization'],
        'inLanguage' => app()->getLocale(),
        'potentialAction' => $searchUrl ? [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => $searchUrl.'{search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ] : null,
    ]);
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
