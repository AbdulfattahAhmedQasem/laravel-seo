<?php

use IProfile\Seo\Tests\Fixtures\Article;

beforeEach(function () {
    config([
        'seo.sitemap.static_urls' => [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/contact', 'priority' => '0.7'],
        ],
        'seo.sitemap.groups' => [
            'articles' => [
                'model' => Article::class,
                'scope' => 'published',
                'priority' => '0.9',
                'changefreq' => 'monthly',
            ],
        ],
    ]);
});

function xml(string $body): DOMDocument
{
    $doc = new DOMDocument;
    expect($doc->loadXML($body))->toBeTrue();

    return $doc;
}

it('ينتج فهرساً صالحاً يسرد كل المجموعات', function () {
    Article::create(['slug' => 'x', 'title' => 'س', 'published_at' => now()]);

    $body = $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->streamedContent();

    $doc = xml($body);

    expect($doc->documentElement->tagName)->toBe('sitemapindex')
        ->and($body)->toContain('/sitemap-static.xml')
        ->and($body)->toContain('/sitemap-articles.xml');
});

it('يشتق lastmod من آخر تعديل حقيقي لا من وقت الطلب', function () {
    $touched = now()->subYear()->startOfHour();

    // إدراج مباشر: create() ستضبط updated_at إلى الآن فتُخفي العيب.
    Article::insert([
        'slug' => 'a', 'title' => 'أ',
        'published_at' => $touched, 'created_at' => $touched, 'updated_at' => $touched,
    ]);

    $body = $this->get('/sitemap.xml')->streamedContent();

    /*
     | التصميم السابق كان <lastmod>{{ now() }}</lastmod> على كل مجموعة:
     | يُعلن تغييراً في كل زحفة حتى يتوقف جوجل عن تصديق lastmod كلياً،
     | فتخسر الإشارة الوحيدة التي تسرّع إعادة الزحف للمحتوى المحدَّث.
     */
    expect($body)->toContain($touched->toAtomString())
        ->and($body)->not->toContain(now()->toAtomString());
});

it('ينتج روابط المجموعة بترميز XML صالح', function () {
    Article::create(['slug' => 'first', 'title' => 'أول', 'published_at' => now()]);
    Article::create(['slug' => 'draft', 'title' => 'مسودة', 'published_at' => null]);

    $body = $this->get('/sitemap-articles.xml')->assertOk()->streamedContent();
    $doc = xml($body);

    expect($doc->getElementsByTagName('url'))->toHaveCount(1)
        ->and($body)->toContain(url('/articles/first'))
        ->and($body)->not->toContain('draft');
});

it('يهرب المحارف الخاصة في الروابط', function () {
    config(['seo.sitemap.static_urls' => [['loc' => '/search?q=a&b=1']]]);

    $body = $this->get('/sitemap-static.xml')->streamedContent();

    xml($body); // يفشل التحليل إن لم يُهرب &
    expect($body)->toContain('&amp;');
});

it('يقسّم المجموعة الكبيرة إلى صفحات ضمن حد المواصفة', function () {
    config(['seo.sitemap.max_urls_per_file' => 2]);

    collect(range(1, 5))->each(fn ($i) => Article::create([
        'slug' => "a{$i}", 'title' => "مقال {$i}", 'published_at' => now(),
    ]));

    $index = $this->get('/sitemap.xml')->streamedContent();

    expect($index)->toContain('/sitemap-articles-1.xml')
        ->and($index)->toContain('/sitemap-articles-3.xml');

    $page = $this->get('/sitemap-articles-1.xml')->assertOk()->streamedContent();
    expect(xml($page)->getElementsByTagName('url'))->toHaveCount(2);

    $last = $this->get('/sitemap-articles-3.xml')->assertOk()->streamedContent();
    expect(xml($last)->getElementsByTagName('url'))->toHaveCount(1);
});

it('لا يحمّل كل الصفوف في الذاكرة عند التوليد', function () {
    config(['seo.sitemap.chunk_size' => 2]);

    collect(range(1, 10))->each(fn ($i) => Article::create([
        'slug' => "b{$i}", 'title' => "مقال {$i}", 'published_at' => now(),
    ]));

    $peakBefore = memory_get_peak_usage();
    $body = $this->get('/sitemap-articles.xml')->streamedContent();

    expect(xml($body)->getElementsByTagName('url'))->toHaveCount(10)
        ->and(memory_get_peak_usage() - $peakBefore)->toBeLessThan(4 * 1024 * 1024);
});

it('يعيد 404 لمجموعة غير معرّفة', function () {
    $this->get('/sitemap-unknown.xml')->assertNotFound();
});

it('يضبط ترويسة كاش تسمح للـ CDN بخدمة الزواحف', function () {
    $ttl = config('seo.sitemap.cache_ttl');

    $this->get('/sitemap.xml')->assertHeader('Cache-Control', "max-age={$ttl}, public");
    $this->get('/robots.txt')->assertHeader('Cache-Control', "max-age={$ttl}, public");
});

it('لا يدرج مجموعة فارغة في الفهرس', function () {
    // ملف خريطة فارغ يظهر في Search Console كتحذير بلا فائدة.
    $body = $this->get('/sitemap.xml')->streamedContent();

    expect($body)->not->toContain('/sitemap-articles.xml');
});

it('robots.txt يمنع الفهرسة خارج الإنتاج', function () {
    $this->asLocalEnvironment();

    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false);
});

it('robots.txt يسمح ويشير إلى الفهرس في الإنتاج', function () {
    $body = $this->get('/robots.txt')->assertOk()->getContent();

    expect($body)->toContain('Allow: /')
        ->and($body)->toContain('Sitemap: '.url('/sitemap.xml'));
});
