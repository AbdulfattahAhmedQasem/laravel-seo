<?php

use IProfile\Seo\Facades\Seo;

function meta(): string
{
    return view('seo::components.meta')->render();
}

it('يطبع كل الوسوم الأساسية', function () {
    Seo::title('خدماتنا')->description('وصف الصفحة');

    $html = meta();

    expect($html)->toContain('<title>خدماتنا | موقع الاختبار</title>')
        ->toContain('<meta name="description" content="وصف الصفحة">')
        ->toContain('<link rel="canonical"')
        ->toContain('<meta name="robots" content="index, follow')
        ->toContain('property="og:title" content="خدماتنا"')
        ->toContain('name="twitter:card"');
});

it('لا يكرر اسم الموقع في og:title', function () {
    Seo::title('خدماتنا');

    // بطاقة المشاركة ضيقة؛ تكرار اسم الموقع فيها يهدر المساحة المرئية.
    expect(meta())->toContain('property="og:title" content="خدماتنا"')
        ->and(meta())->not->toContain('property="og:title" content="خدماتنا | موقع الاختبار"');
});

it('يهرب المحارف الخاصة في وسوم meta', function () {
    Seo::title('عنوان "مزدوج" & <وسم>');

    $html = meta();

    expect($html)->not->toContain('<وسم>')
        ->and($html)->toContain('&amp;');
});

it('يفرض noindex خارج الإنتاج مهما كان الإعداد', function () {
    $this->asLocalEnvironment();
    Seo::title('ص')->robots('index, follow');

    expect(meta())->toContain('content="noindex, nofollow"');
});

it('لا يطبع أبعاد og:image ما لم تُضبط صراحةً', function () {
    config(['seo.default_image' => '/img/a.jpg']);
    Seo::title('ص');

    expect(meta())->not->toContain('og:image:width');

    config(['seo.og_image_size' => ['width' => 1200, 'height' => 630]]);
    expect(meta())->toContain('content="1200"');
});

it('لا يطبع hreflang لبديل وحيد', function () {
    Seo::title('ص')->alternates(['ar' => url('/ar')]);

    // hreflang ببديل واحد بلا معنى، وجوجل يطلب التبادلية.
    expect(meta())->not->toContain('hreflang');
});

it('يطبع hreflang و x-default لبديلين فأكثر', function () {
    Seo::title('ص')->alternates(['ar' => url('/ar'), 'en' => url('/en')]);

    expect(meta())->toContain('hreflang="ar-SA"')
        ->toContain('hreflang="en"')
        ->toContain('hreflang="x-default"');
});

it('يتجاهل تاريخاً غير صالح بدل إسقاط الصفحة', function () {
    Seo::title('ص')->article('ليس تاريخاً');

    expect(meta())->not->toContain('article:published_time');
});
