<?php

namespace IProfile\Seo\Http\Controllers;

use IProfile\Seo\Support\SitemapGenerator;
use IProfile\Seo\Support\SitemapWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController
{
    /**
     * فهرس خرائط الموقع. يسرد كل مجموعة، ومجموعة كبيرة تظهر كصفحات.
     */
    public function index(): Response
    {
        abort_unless(config('seo.sitemap.enabled', true), 404);

        $groups = SitemapGenerator::groups();

        // بلا مجموعات لا معنى لفهرس من ملف واحد — نخدم الملف مباشرة.
        if ($groups === []) {
            return $this->group(SitemapGenerator::STATIC_GROUP);
        }

        $entries = [];

        foreach (array_merge([SitemapGenerator::STATIC_GROUP], $groups) as $group) {
            $meta = SitemapGenerator::meta($group);

            if ($meta['count'] === 0) {
                continue;
            }

            for ($page = 1; $page <= $meta['pages']; $page++) {
                $entries[] = [
                    'loc' => url($meta['pages'] === 1
                        ? "/sitemap-{$group}.xml"
                        : "/sitemap-{$group}-{$page}.xml"),
                    // آخر تعديل حقيقي، لا now() التي كانت تُعلن تغييراً
                    // في كل زحفة حتى يتوقف جوجل عن تصديق lastmod كلياً.
                    'lastmod' => $meta['lastmod'],
                ];
            }
        }

        return $this->stream(fn () => SitemapWriter::index($entries));
    }

    /**
     * ملف روابط مجموعة واحدة، اختيارياً صفحة منها.
     */
    public function group(string $group, int|string|null $page = null): Response
    {
        $page = $page === null ? null : (int) $page;

        abort_unless(config('seo.sitemap.enabled', true), 404);
        abort_unless(SitemapGenerator::hasGroup($group), 404);

        if ($page !== null) {
            abort_if($page < 1 || $page > SitemapGenerator::meta($group)['pages'], 404);
        }

        return $this->stream(
            fn () => SitemapWriter::urlset(SitemapGenerator::urls($group, $page))
        );
    }

    public function robots(): Response
    {
        abort_unless(config('seo.robots.enabled', true), 404);

        $indexable = in_array(
            app()->environment(),
            (array) config('seo.indexable_environments', ['production']),
            true
        );

        if (! $indexable) {
            $body = "User-agent: *\nDisallow: /";
        } else {
            $lines = ['User-agent: *', 'Allow: /'];

            foreach ((array) config('seo.robots.disallow', []) as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            if (config('seo.sitemap.enabled', true)) {
                $lines[] = '';
                $lines[] = 'Sitemap: '.url((string) config('seo.sitemap.route', '/sitemap.xml'));
            }

            $body = implode("\n", $lines);
        }

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => $this->cacheControl(),
        ]);
    }

    /**
     * البثّ يبقي الذاكرة ثابتة مهما كبرت الخريطة: كل رابط يُكتب إلى
     * المخرج ثم يُنسى، بدل بناء سلسلة بحجم الملف كاملاً في الذاكرة.
     */
    protected function stream(\Closure $body): StreamedResponse
    {
        return response()->stream(
            $body,
            200,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Cache-Control' => $this->cacheControl(),
            ]
        );
    }

    protected function cacheControl(): string
    {
        /*
        | مدّتان لا واحدة: `cache_ttl` كم نحتفظ نحن بالخريطة — نُبطله متى
        | شئنا بـSitemapCache::flush()‎ — و`http_ttl` كم يُسمح لغيرنا
        | بالاحتفاظ بها، وهذا ما **لا نملك إبطاله**: شبكة التوصيل تحتفظ
        | بالاستجابة المدّة المعلنة ولا يصلها إبطالنا.
        |
        | قِيس الأثر على fastnawras.com في 2026-09-20: أُضيفت أربع عشرة
        | صفحة، وأُبطل الكاش عندنا، فبقيت الحافة تعلن أربعاً وعشرين رابطاً
        | ساعاتٍ لأن القيمة الواحدة كانت تذهب إلى الترويسة أيضاً.
        */
        return 'max-age='.(int) config('seo.sitemap.http_ttl', 300).', public';
    }
}
