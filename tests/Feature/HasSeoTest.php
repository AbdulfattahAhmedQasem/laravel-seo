<?php

use Illuminate\Support\Facades\DB;
use IProfile\Seo\Facades\Seo;
use IProfile\Seo\Models\SeoMeta;
use IProfile\Seo\Models\SeoRedirect;
use IProfile\Seo\SeoManager;
use IProfile\Seo\Tests\Fixtures\Article;

it('يشتق العنوان من حقل title', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'عنوان المقال']);

    expect($article->seoData()->title)->toBe('عنوان المقال');
});

it('يشتق الوصف من excerpt ثم body', function () {
    $withExcerpt = Article::create(['slug' => 'a', 'excerpt' => '<p>مقتطف   نظيف</p>']);
    $withBody = Article::create(['slug' => 'b', 'body' => '<div>نص المتن</div>']);

    expect($withExcerpt->seoData()->description)->toBe('مقتطف نظيف')
        ->and($withBody->seoData()->description)->toBe('نص المتن');
});

it('يشتق الصورة من cover_image', function () {
    $article = Article::create(['slug' => 'a', 'cover_image' => '/img/a.jpg']);

    expect($article->seoData()->resolvedImage())->toBe(url('/img/a.jpg'));
});

it('يفضّل بيانات قاعدة البيانات على الاشتقاق', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'مشتق']);
    $article->seo()->create(['locale' => app()->getLocale(), 'title' => 'مخصص']);

    expect($article->fresh()->seoData()->title)->toBe('مخصص');
});

it('ينشئ تحويل 301 عند تغيير الـ slug', function () {
    $article = Article::create(['slug' => 'old-slug', 'title' => 'أ']);
    $article->update(['slug' => 'new-slug']);

    expect(SeoRedirect::where('from', '/articles/old-slug')->value('to'))
        ->toBe('/articles/new-slug');
});

it('لا يطلق أحداث الموديل أثناء بناء الرابط القديم', function () {
    $article = Article::create(['slug' => 'old', 'title' => 'أ']);

    $fired = 0;
    Article::retrieved(function () use (&$fired) { $fired++; });
    Article::saving(function () use (&$fired) { $fired++; });

    $article->update(['slug' => 'new']);

    // saving واحد فقط للتحديث نفسه — لا حدث إضافي من النسخة المؤقتة.
    expect($fired)->toBe(1);
});

it('لا ينشئ تحويلاً إن لم يتغير الرابط فعلياً', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'أ']);
    $article->update(['title' => 'عنوان جديد']);

    expect(SeoRedirect::count())->toBe(0);
});

it('يحمّل علاقة seo مسبقاً فلا يقع في N+1', function () {
    collect(range(1, 5))->each(fn ($i) => Article::create(['slug' => "a{$i}", 'title' => "م{$i}"]));

    DB::enableQueryLog();
    Article::withSeo()->get()->each(fn ($a) => $a->seoData());

    // استعلام للمقالات + استعلام واحد للعلاقة = 2. بلا التحميل المسبق: 6.
    expect(DB::getQueryLog())->toHaveCount(2);
});

it('يحذف بيانات seo عند حذف الموديل', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'أ']);
    $article->seo()->create(['locale' => app()->getLocale(), 'title' => 'س']);

    $article->delete();

    expect(SeoMeta::count())->toBe(0);
});

it('Seo::for يعيد المدير فتبقى السلسلة متسقة', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'أ']);

    expect(Seo::for($article))->toBeInstanceOf(SeoManager::class)
        ->and(Seo::for($article)->crumb('الرئيسية', url('/')))->toBeInstanceOf(SeoManager::class);
});

it('يرفض Seo::for لموديل بلا HasSeo برسالة واضحة', function () {
    $model = new class extends Illuminate\Database\Eloquent\Model {};

    expect(fn () => Seo::for($model))->toThrow(InvalidArgumentException::class, 'HasSeo');
});
