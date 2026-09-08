<?php

use IProfile\Seo\Data\SeoData;

it('يضيف لاحقة الموقع إلى العنوان', function () {
    config(['seo.title_suffix' => 'اي بروفايل', 'seo.title_separator' => ' | ']);

    expect(SeoData::make('تصميم مواقع')->fullTitle())
        ->toBe('تصميم مواقع | اي بروفايل');
});

it('لا يكرر اللاحقة إن كانت موجودة', function () {
    config(['seo.title_suffix' => 'اي بروفايل']);

    expect(SeoData::make('اي بروفايل')->fullTitle())->toBe('اي بروفايل');
});

it('يقص الوصف وينظف الوسوم', function () {
    $data = SeoData::make()->description('<p>نص   طويل</p>', 8);

    expect($data->description)->toBe('نص طويل');
});

it('يفرض noindex خارج بيئة الإنتاج', function () {
    app()['env'] = 'local';
    config(['seo.indexable_environments' => ['production']]);

    expect(SeoData::make('اختبار')->resolvedRobots())->toBe('noindex, nofollow');
});
