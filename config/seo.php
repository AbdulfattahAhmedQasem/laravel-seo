<?php

return [

    /*
    |--------------------------------------------------------------------------
    | الهوية العامة
    |--------------------------------------------------------------------------
    */

    'title_suffix' => env('SEO_TITLE_SUFFIX', config('app.name')),

    'title_separator' => ' | ',

    'default_title' => env('SEO_DEFAULT_TITLE', config('app.name')),

    'default_description' => env('SEO_DEFAULT_DESCRIPTION'),

    'default_image' => env('SEO_DEFAULT_IMAGE', '/img/og-default.jpg'),

    'default_robots' => 'index, follow, max-image-preview:large, max-snippet:-1',

    /*
    | يمنع فهرسة بيئات التطوير والاختبار تلقائياً.
    */
    'indexable_environments' => ['production'],

    'twitter_handle' => env('SEO_TWITTER'),

    /*
    |--------------------------------------------------------------------------
    | الحدود الموصى بها (تُستخدم في التحقق وفي لوحة التحكم)
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'title'       => ['min' => 30,  'max' => 60],
        'description' => ['min' => 120, 'max' => 160],
    ],

    /*
    |--------------------------------------------------------------------------
    | بيانات المنشأة — تُطبع كـ JSON-LD في كل صفحة
    |--------------------------------------------------------------------------
    | @type يمكن أن يكون: Organization | ProfessionalService | LocalBusiness
    */

    'organization' => [
        'enabled'    => true,
        'type'       => 'ProfessionalService',
        'name'       => env('SEO_ORG_NAME', config('app.name')),
        'legal_name' => env('SEO_ORG_LEGAL_NAME'),
        'logo'       => '/img/logo.png',
        'phone'      => env('SEO_ORG_PHONE'),
        'email'      => env('SEO_ORG_EMAIL'),
        'founding_date' => null,
        'address' => [
            'street'  => null,
            'city'    => null,
            'region'  => null,
            'zip'     => null,
            'country' => 'SA',
        ],
        'geo' => [
            'lat' => null,
            'lng' => null,
        ],
        'social' => [
            // 'https://twitter.com/...',
            // 'https://www.linkedin.com/company/...',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | خريطة الموقع
    |--------------------------------------------------------------------------
    | كل مجموعة: مفتاح => إعدادات المصدر.
    | 'model' يجب أن يستخدم trait المسار HasSeo وأن يعرّف toSitemapUrl().
    */

    'sitemap' => [
        'enabled'    => true,
        'route'      => '/sitemap.xml',
        'cache_ttl'  => 60 * 60 * 6, // 6 ساعات
        'groups'     => [
            // 'services' => [
            //     'model' => \App\Models\Service::class,
            //     'scope' => 'published',
            //     'priority' => '0.9',
            //     'changefreq' => 'monthly',
            // ],
        ],
        'static_urls' => [
            // ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | robots.txt
    |--------------------------------------------------------------------------
    */

    'robots' => [
        'enabled'  => true,
        'disallow' => ['/admin', '/login', '/register', '/*?s='],
    ],

    /*
    |--------------------------------------------------------------------------
    | التحويلات 301
    |--------------------------------------------------------------------------
    */

    'redirects' => [
        'enabled'        => true,
        'cache_ttl'      => 60 * 60,
        'track_hits'     => true,
        // إنشاء تحويل تلقائي عند تغيير الـ slug لأي موديل يستخدم HasSeo
        'auto_on_slug_change' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | اللغات
    |--------------------------------------------------------------------------
    */

    'locales' => ['ar', 'en'],

    'hreflang' => [
        'enabled'   => true,
        'x_default' => 'ar',
        'map'       => ['ar' => 'ar-SA', 'en' => 'en'],
    ],
];
