<?php

use IProfile\Seo\Facades\Seo;
use IProfile\Seo\Support\Json;

it('يرمّز وسوم script داخل JSON-LD فلا يمكن كسر البلوك', function () {
    $payload = '</script><script>alert(1)</script>';

    $encoded = Json::forScript(['name' => $payload]);

    expect($encoded)->not->toContain('</script>')
        ->and($encoded)->not->toContain('<script>')
        ->and($encoded)->toContain(chr(92).'u003C');
});

it('يحافظ على العربية مقروءة رغم الترميز الآمن', function () {
    expect(Json::forScript(['name' => 'شركة النوارس']))
        ->toContain('شركة النوارس');
});

it('لا يسمح بحقن script عبر عنوان مقال في BreadcrumbList', function () {
    Seo::title('عادي')->crumb('</script><script>alert(1)</script>', url('/x'));

    $html = view('seo::components.meta')->render();

    expect($html)->not->toContain('<script>alert(1)</script>')
        ->and(substr_count($html, '</script>'))->toBe(substr_count($html, '<script type="application/ld+json">'));
});

it('لا يسمح بحقن script عبر schema مخصص', function () {
    Seo::title('عادي')->schema([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => '</script><img src=x onerror=alert(1)>',
    ]);

    $html = view('seo::components.meta')->render();

    expect($html)->not->toContain('</script><img');
});

it('لا يطبع false عند فشل الترميز', function () {
    // بايت UTF-8 غير صالح — json_encode يعيد false
    expect(Json::forScript(['name' => "\xB1\x31"]))->toBe('{}');
});

it('يحذف القيم الفارغة من البيانات المنظمة', function () {
    expect(Json::prune(['a' => 'x', 'b' => null, 'c' => '', 'd' => [], 'e' => 0]))
        ->toBe(['a' => 'x', 'e' => 0]);
});
