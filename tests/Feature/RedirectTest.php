<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use IProfile\Seo\Http\Middleware\HandleSeoRedirects;
use IProfile\Seo\Models\SeoRedirect;

beforeEach(function () {
    /*
     | عالمياً لا في مجموعة web: الطلب الذي لا يطابق أي روط لا تُنفَّذ عليه
     | مجموعات الروطات إطلاقاً — وهذا بالضبط حال كل رابط قديم.
     */
    $this->app[Kernel::class]->pushMiddleware(HandleSeoRedirects::class);
});

it('يحوّل الرابط القديم بـ 301 ويحفظ معاملات الاستعلام', function () {
    SeoRedirect::create(['from' => '/old', 'to' => '/new', 'status' => 301]);

    $this->get('/old?utm_source=x')
        ->assertStatus(301)
        ->assertHeader('Location', url('/new').'?utm_source=x');
});

it('يترك 404 غير المسجّل كما هو', function () {
    $this->get('/never-existed')->assertStatus(404);
});

it('يرفض تحويلاً يشير إلى نفسه', function () {
    expect(fn () => SeoRedirect::create(['from' => '/a', 'to' => '/a']))
        ->toThrow(InvalidArgumentException::class);
});

it('يسطّح السلسلة أ→ب ثم ب→ج إلى أ→ج', function () {
    SeoRedirect::create(['from' => '/a', 'to' => '/b']);
    SeoRedirect::create(['from' => '/b', 'to' => '/c']);

    expect(SeoRedirect::where('from', '/a')->value('to'))->toBe('/c');
});

it('يمنع الحلقة أ→ب ثم ب→أ', function () {
    SeoRedirect::create(['from' => '/a', 'to' => '/b']);

    expect(fn () => SeoRedirect::create(['from' => '/b', 'to' => '/a']))
        ->toThrow(InvalidArgumentException::class);
});

it('لا يستعلم قاعدة البيانات لطلبات الأصول', function () {
    SeoRedirect::create(['from' => '/old.png', 'to' => '/new.png']);
    Cache::flush();

    DB::enableQueryLog();
    $this->get('/missing-image.png');

    expect(DB::getQueryLog())->toBeEmpty();
});

it('لا يحمّل كل التحويلات في الذاكرة عند البحث عن واحد', function () {
    SeoRedirect::insert(collect(range(1, 200))->map(fn ($i) => [
        'from' => "/old-{$i}", 'to' => "/new-{$i}", 'status' => 301,
    ])->all());

    Cache::flush();
    DB::enableQueryLog();

    SeoRedirect::lookup('/old-7');

    $log = DB::getQueryLog();
    expect($log)->toHaveCount(1)
        ->and($log[0]['query'])->toContain('limit 1');
});

it('يدعم 410 للمحتوى المحذوف نهائياً', function () {
    SeoRedirect::create(['from' => '/gone', 'to' => '/', 'status' => 410]);

    $this->get('/gone')->assertStatus(410);
});

it('يتجاهل التحويل على الطلبات غير الآمنة فلا يفقد جسم POST', function () {
    SeoRedirect::create(['from' => '/old', 'to' => '/new']);

    $this->post('/old', ['x' => 1])->assertStatus(404);
});
