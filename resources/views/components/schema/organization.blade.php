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

        /*
        | ساعات العمل ونطاق الخدمة — عنصران يقرأهما البحث المحلي.
        |
        | الحزمة تطبع ما يصلها ولا تخمّن: `opening_hours` مواصفةٌ واحدة أو
        | قائمة مواصفات كما في schema.org، و`area_served` أسماء مدن. من
        | يملأهما هو التطبيق (جسر بيانات المنشأة)، فتبقى الحزمة محايدة.
        */
        'openingHoursSpecification' => ! empty($org['opening_hours'])
            ? array_map(
                fn (array $spec): array => ['@type' => 'OpeningHoursSpecification'] + $spec,
                array_is_list($org['opening_hours']) ? $org['opening_hours'] : [$org['opening_hours']],
            )
            : null,

        'areaServed' => ! empty($org['area_served'])
            ? array_map(
                fn (string $name): array => ['@type' => 'City', 'name' => $name],
                array_values(array_filter((array) $org['area_served'])),
            )
            : null,
    ];
@endphp
<x-seo::schema.json :data="$data" />
