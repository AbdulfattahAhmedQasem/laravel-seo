<?php

use IProfile\Seo\Support\CanonicalUrl;

it('يزيل معاملات التتبّع ويبقي المسموح', function () {
    $url = CanonicalUrl::for('https://site.test/blog?utm_source=fb&fbclid=1&page=2&gclid=x');

    expect($url)->toBe('https://site.test/blog?page=2');
});

it('يرتّب المعاملات فلا ينتج canonical مختلفاً لنفس الصفحة', function () {
    config(['seo.canonical.allowed_query' => ['page', 'sort']]);

    expect(CanonicalUrl::for('https://site.test/a?sort=x&page=2'))
        ->toBe(CanonicalUrl::for('https://site.test/a?page=2&sort=x'));
});

it('يوحّد المضيف والمنفذ الافتراضي والشرطة النهائية', function () {
    expect(CanonicalUrl::for('https://SITE.test:443/blog/'))
        ->toBe('https://site.test/blog');
});

it('يبقي المنفذ غير الافتراضي', function () {
    expect(CanonicalUrl::for('http://site.test:8080/a'))->toBe('http://site.test:8080/a');
});

it('يحترم إعداد الشرطة النهائية', function () {
    config(['seo.canonical.trailing_slash' => true]);

    expect(CanonicalUrl::for('https://site.test/blog'))->toBe('https://site.test/blog/');
});

it('يعيد الرابط كما هو إن تعذّر تحليله', function () {
    expect(CanonicalUrl::for('ليس رابطاً'))->toBe('ليس رابطاً');
});
