<?php

use IProfile\Seo\Models\SeoRedirect;
use IProfile\Seo\Support\SitemapCache;

it('seo:clear يبطل كاش التحويلات والخريطة', function () {
    $redirectVersion = SeoRedirect::version();
    $sitemapVersion = SitemapCache::version();

    $this->artisan('seo:clear')->assertSuccessful();

    expect(SeoRedirect::version())->toBe($redirectVersion + 1)
        ->and(SitemapCache::version())->toBe($sitemapVersion + 1);
});

it('seo:clear يجعل تحويلاً محذوفاً من الكاش يُقرأ من جديد', function () {
    SeoRedirect::create(['from' => '/a', 'to' => '/b']);
    SeoRedirect::lookup('/a');

    // حذف مباشر بلا أحداث — يحاكي تعديلاً خارج التطبيق
    SeoRedirect::query()->where('from', '/a')->delete();

    $this->artisan('seo:clear');

    expect(SeoRedirect::lookup('/a'))->toBeNull();
});

it('seo:install ينشر الإعدادات والمايجريشن', function () {
    $config = config_path('seo.php');
    $existing = glob(database_path('migrations/*_create_seo_meta_table.php')) ?: [];

    foreach (array_merge([$config], $existing) as $file) {
        @unlink($file);
    }

    $this->artisan('seo:install', ['--force' => true])
        ->expectsConfirmation('تشغيل المايجريشن الآن؟', 'no')
        ->assertSuccessful();

    expect(file_exists($config))->toBeTrue()
        ->and(glob(database_path('migrations/*_create_seo_meta_table.php')))->not->toBeEmpty()
        ->and(glob(database_path('migrations/*_create_seo_redirects_table.php')))->not->toBeEmpty();
});

it('seo:install يفشل صراحةً إن لم يُنشر شيء بدل ادعاء النجاح', function () {
    /*
     | العيب الأصلي: وسوم نشر خاطئة + callSilently تبتلع رسالة
     | "No publishable resources"، فيطبع الأمر DONE مرتين ولا يُنشر شيء.
     | هنا نجعل النشر مستحيلاً فعلياً (مسار إعدادات داخل ملف لا مجلد)
     | ونتأكد أن الأمر يعترف بالفشل بدل ادعاء النجاح.
     */
    $blocker = tempnam(sys_get_temp_dir(), 'seo');
    $this->app->useConfigPath($blocker.'/config');

    $this->artisan('seo:install', ['--force' => true])
        ->assertFailed();

    @unlink($blocker);
});
