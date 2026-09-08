@php
    $org = config('seo.organization', []);
    $home = rtrim(url('/'), '/');

    $data = [
        '@context'      => 'https://schema.org',
        '@type'         => $org['type'] ?? 'Organization',
        '@id'           => $home.'/#organization',
        'name'          => $org['name'] ?? config('app.name'),
        'alternateName' => $org['legal_name'] ?? null,
        'url'           => $home.'/',
        'logo'          => ! empty($org['logo']) ? asset($org['logo']) : null,
        'image'         => ! empty($org['logo']) ? asset($org['logo']) : null,
        'telephone'     => $org['phone'] ?? null,
        'email'         => $org['email'] ?? null,
        'foundingDate'  => $org['founding_date'] ?? null,
        'address'       => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $org['address']['street'] ?? null,
            'addressLocality' => $org['address']['city'] ?? null,
            'addressRegion'   => $org['address']['region'] ?? null,
            'postalCode'      => $org['address']['zip'] ?? null,
            'addressCountry'  => $org['address']['country'] ?? null,
        ],
        'geo' => ! empty($org['geo']['lat']) && ! empty($org['geo']['lng']) ? [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $org['geo']['lat'],
            'longitude' => $org['geo']['lng'],
        ] : null,
        'sameAs' => ! empty($org['social']) ? array_values(array_filter($org['social'])) : null,
    ];
@endphp
<x-seo::schema.json :data="$data" />
