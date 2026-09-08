@props(['items' => []])
@php
    $data = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['name'] ?? null,
            'item'     => $item['url'] ?? null,
        ])->all(),
    ];
@endphp
<x-seo::schema.json :data="$data" />
