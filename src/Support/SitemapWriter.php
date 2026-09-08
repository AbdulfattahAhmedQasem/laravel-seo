<?php

namespace IProfile\Seo\Support;

/**
 * يكتب XML خريطة الموقع مباشرة إلى المخرج، رابطاً برابط.
 *
 * لماذا لا Blade: قالب Blade يُبنى كسلسلة واحدة في الذاكرة قبل أن
 * يُرسَل، فـ "البثّ" عبره وهم — ملف بـ 45 ألف رابط يعني ~8 ميجابايت
 * في الذاكرة لكل طلب متزامن. هنا كل رابط يُكتب ثم يُنسى.
 *
 * ولأن القوالب تُغري بمسافة قبل <?xml فتُبطل الملف كاملاً، الكتابة
 * المباشرة أكثر أماناً لا أقل.
 */
final class SitemapWriter
{
    /**
     * @param  iterable<int,array<string,mixed>>  $urls
     */
    public static function urlset(iterable $urls): void
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            self::urlNode($url);
        }

        echo '</urlset>';
    }

    /**
     * @param  iterable<int,array{loc:string,lastmod:?string}>  $entries
     */
    public static function index(iterable $entries): void
    {
        echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($entries as $entry) {
            echo "    <sitemap>\n";
            echo '        <loc>'.self::escape($entry['loc'])."</loc>\n";

            if (! empty($entry['lastmod'])) {
                echo '        <lastmod>'.self::escape($entry['lastmod'])."</lastmod>\n";
            }

            echo "    </sitemap>\n";
        }

        echo '</sitemapindex>';
    }

    /**
     * @param  array<string,mixed>  $url
     */
    protected static function urlNode(array $url): void
    {
        echo "    <url>\n";
        echo '        <loc>'.self::escape((string) $url['loc'])."</loc>\n";

        foreach (['lastmod', 'changefreq', 'priority'] as $tag) {
            if (! empty($url[$tag])) {
                echo "        <{$tag}>".self::escape((string) $url[$tag])."</{$tag}>\n";
            }
        }

        echo "    </url>\n";
    }

    /**
     * المواصفة تلزم بهروب الكيانات الخمسة. رابط فيه & غير مهروب
     * يجعل الملف كاملاً غير صالح فيرفضه جوجل بلا إشعار مفيد.
     */
    protected static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8', false);
    }
}
