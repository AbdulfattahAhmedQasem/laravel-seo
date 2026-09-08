<?php

use IProfile\Seo\Data\SeoData;
use IProfile\Seo\Facades\Seo;
use IProfile\Seo\SeoManager;
use IProfile\Seo\Support\SeoScore;

it('ملف الإعدادات قابل للتسلسل فينجح config:cache', function () {
    $config = require __DIR__.'/../../config/seo.php';

    // إغلاق (Closure) داخل الإعدادات يُسقط config:cache بالكامل.
    $encoded = var_export($config, true);

    expect($encoded)->toBeString()
        ->and($config)->toBeArray();
});

it('لا يعتمد ملف الإعدادات على إعدادات أخرى وقت التحميل', function () {
    $source = file_get_contents(__DIR__.'/../../config/seo.php');

    // config() داخل ملف إعدادات: الترتيب غير مضمون وقد يُخزَّن null للأبد.
    expect(preg_match('/^\s*[^|*\/]*\bconfig\(/m', $source))->toBe(0);
});

it('اسم الموقع يُحلّ وقت التنفيذ لا وقت تحميل الإعدادات', function () {
    config(['seo.title_suffix' => null, 'app.name' => 'اسم متأخر']);

    expect(SeoData::make('ص')->fullTitle())->toBe('ص | اسم متأخر');
});

it('المدير scoped فتُعاد تهيئته بين الطلبات تحت Octane', function () {
    Seo::title('طلب أول');
    expect(Seo::data()->title)->toBe('طلب أول');

    // ما يفعله Octane بين الطلبات
    app()->forgetScopedInstances();

    expect(app(SeoManager::class)->data()->title)->toBeNull();
});

it('يرفض استدعاء دالة غير موجودة برسالة واضحة لا بخطأ غامض', function () {
    expect(fn () => Seo::لا_توجد('x'))->toThrow(BadMethodCallException::class);
});

it('يعمل بلا ملف إعدادات منشور معتمداً على قيم الحزمة', function () {
    config(['seo' => null]);

    expect(fn () => SeoData::make('ص')->toArray())->not->toThrow(Throwable::class);
});

it('SeoScore يحسب نتيجة متسقة ولا ينهار على مدخلات فارغة', function () {
    $empty = SeoScore::analyze(null, null);
    $good = SeoScore::analyze(
        title: 'خدمات تصميم المواقع الاحترافية في السعودية',
        description: 'نصمم مواقع سريعة ومهيأة لمحركات البحث. خدمات تصميم المواقع لدينا تشمل التحليل والتنفيذ والصيانة لسنوات.',
        keyword: 'تصميم المواقع',
        body: str_repeat('كلمة ', 400),
        slug: 'تصميم-المواقع',
    );

    expect($empty['score'])->toBe(0)
        ->and($empty['checks'])->not->toBeEmpty()
        ->and($good['score'])->toBeGreaterThan(80);
});

it('SeoScore يعدّ الكلمات العربية بشكل صحيح لا بالبايتات', function () {
    /*
     | str_word_count يعدّ البايتات: 100 كلمة عربية كانت تُحسب ~450
     | فيمرّ فحص "300 كلمة" على نص قصير جداً — كذب على كاتب المحتوى
     | في اللغة التي بُنيت الحزمة لها.
     */
    expect(SeoScore::countWords(str_repeat('كلمة عربية ', 50)))->toBe(100)
        ->and(SeoScore::countWords(str_repeat('word here ', 50)))->toBe(100)
        ->and(SeoScore::countWords('<p>نص  فيه   وسوم</p>'))->toBe(3)
        ->and(SeoScore::countWords(null))->toBe(0);

    $short = SeoScore::analyze('ع', 'و', null, str_repeat('كلمة عربية ', 50));
    expect(collect($short['checks'])->firstWhere('key', 'content_length')['passed'])->toBeFalse();
});
