@props(['items' => []])
@php
    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => collect($items)->map(fn ($item) => [
            '@type'          => 'Question',
            'name'           => $item['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['answer'])],
        ])->all(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
