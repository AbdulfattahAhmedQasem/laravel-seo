<?php

/*
 | كل مقتطف في README يُنفَّذ هنا. الوثائق التي لا تُختبر تكذب بمرور
 | الوقت، وأسوأ ما في الحزمة الخاصة أن يثق صاحبها بمثال لا يعمل.
 */

use Illuminate\View\ComponentAttributeBag;
use IProfile\Seo\Facades\Seo;
use IProfile\Seo\Support\SeoScore;
use IProfile\Seo\Tests\Fixtures\Article;

it('README: الحد الأدنى — Seo::for($model)', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'خدمة', 'excerpt' => 'وصف']);

    Seo::for($article);

    expect(Seo::data()->title)->toBe('خدمة')
        ->and(Seo::data()->description)->toBe('وصف');
});

it('README: التحكم الكامل بالسلسلة', function () {
    $article = Article::create(['slug' => 'a', 'title' => 'مقال', 'published_at' => now()]);

    Seo::for($article)
        ->article($article->published_at, $article->updated_at, 'الكاتب')
        ->crumb('الرئيسية', url('/'))
        ->crumb('المدونة', url('/blog'))
        ->crumb($article->title, $article->seoUrl())
        ->alternates(['ar' => url('/ar/a'), 'en' => url('/en/a')])
        ->schema([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article->title,
        ]);

    $html = view('seo::components.meta')->render();

    expect(Seo::data()->breadcrumbs)->toHaveCount(3)
        ->and($html)->toContain('BlogPosting')
        ->and($html)->toContain('article:published_time')
        ->and($html)->toContain('hreflang="x-default"');
});

it('README: صفحة بلا موديل', function () {
    Seo::title('من نحن')->description('شركة اي بروفايل متخصصة في ...');

    expect(view('seo::components.meta')->render())->toContain('<title>من نحن | موقع الاختبار</title>');
});

it('README: منع الفهرسة', function () {
    expect(Seo::noindex()->data()->resolvedRobots())->toBe('noindex, follow')
        ->and(Seo::noindex(false)->data()->resolvedRobots())->toBe('noindex, nofollow');
});

it('README: مسار التنقل المرئي', function () {
    Seo::crumb('الرئيسية', url('/'))->crumb('خدماتنا', url('/services'));

    $html = view('seo::components.breadcrumbs', ['attributes' => new ComponentAttributeBag])->render();

    expect($html)->toContain('aria-current="page"')
        ->and($html)->toContain('خدماتنا');
});

it('README: مكوّن الأسئلة الشائعة', function () {
    $html = view('seo::components.schema.faq', [
        'items' => [
            ['question' => 'كم تستغرق مدة التنفيذ؟', 'answer' => 'من 2 إلى 6 أسابيع.'],
        ],
        'attributes' => new ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('FAQPage')->toContain('acceptedAnswer');
});

it('README: مربع البحث في نتائج جوجل', function () {
    $html = view('seo::components.schema.website', [
        'searchUrl' => url('/search?q='),
        'attributes' => new ComponentAttributeBag,
    ])->render();

    expect($html)->toContain('SearchAction')->toContain('search_term_string');
});

it('README: SeoScore يعيد score و checks', function () {
    $result = SeoScore::analyze('عنوان', 'وصف', 'كلمة', 'نص', 'slug');

    expect($result)->toHaveKeys(['score', 'checks'])
        ->and($result['score'])->toBeInt();
});

it('README: تعريف مجموعة sitemap يعمل كما هو موصوف', function () {
    config(['seo.sitemap.groups.articles' => [
        'model' => Article::class,
        'scope' => 'published',
        'priority' => '0.9',
        'changefreq' => 'monthly',
    ]]);

    Article::create(['slug' => 'a', 'title' => 'أ', 'published_at' => now()]);

    $body = $this->get('/sitemap-articles.xml')->assertOk()->streamedContent();

    expect($body)->toContain('<priority>0.9</priority>')
        ->toContain('<changefreq>monthly</changefreq>');
});
