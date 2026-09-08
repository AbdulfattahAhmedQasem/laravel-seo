@props(['items' => []])
@php
    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => collect($items)->map(fn ($item) => [
            '@type'          => 'Question',
            'name'           => $item['question'] ?? null,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => isset($item['answer']) ? strip_tags($item['answer']) : null,
            ],
        ])->all(),
    ];
@endphp
<x-seo::schema.json :data="$data" />
