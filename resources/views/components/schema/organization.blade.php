@php
    $org = config('seo.organization');

    $data = array_filter([
        '@context'      => 'https://schema.org',
        '@type'         => $org['type'] ?? 'Organization',
        '@id'           => rtrim(url('/'), '/').'/#organization',
        'name'          => $org['name'] ?? null,
        'alternateName' => $org['legal_name'] ?? null,
        'url'           => url('/'),
        'logo'          => ! empty($org['logo']) ? asset($org['logo']) : null,
        'image'         => ! empty($org['logo']) ? asset($org['logo']) : null,
        'telephone'     => $org['phone'] ?? null,
        'email'         => $org['email'] ?? null,
        'foundingDate'  => $org['founding_date'] ?? null,
        'address'       => array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => $org['address']['street'] ?? null,
            'addressLocality' => $org['address']['city'] ?? null,
            'addressRegion'   => $org['address']['region'] ?? null,
            'postalCode'      => $org['address']['zip'] ?? null,
            'addressCountry'  => $org['address']['country'] ?? null,
        ]),
        'geo' => ! empty($org['geo']['lat']) ? [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $org['geo']['lat'],
            'longitude' => $org['geo']['lng'],
        ] : null,
        'sameAs' => ! empty($org['social']) ? array_values($org['social']) : null,
    ], fn ($v) => ! empty($v) && $v !== ['@type' => 'PostalAddress']);
@endphp
<script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
