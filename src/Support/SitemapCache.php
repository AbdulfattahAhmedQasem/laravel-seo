<?php

namespace IProfile\Seo\Support;

use Illuminate\Support\Facades\Cache;

/**
 * كاش البيانات الوصفية لخريطة الموقع (عدد الروابط وآخر تعديل) — لا محتواها.
 *
 * المحتوى يُبَث ولا يُخزَّن: خريطة بـ 50 ألف رابط تعني ملفاً بعشرات
 * الميجابايت في الكاش، وفكّه عند كل زحفة يكلّف أكثر مما يوفّر.
 */
final class SitemapCache
{
    public const VERSION_KEY = 'seo.sitemap.version';

    public static function key(string $group): string
    {
        // اللغة جزء من المفتاح: موقع متعدد اللغات ينتج روابط مختلفة
        // لكل لغة، والمفتاح الموحّد كان يخدم العربية للإنجليز والعكس.
        return implode('.', ['seo.sitemap.meta', self::version(), app()->getLocale(), $group]);
    }

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @template TValue
     *
     * @param  \Closure():TValue  $callback
     * @return TValue
     */
    public static function remember(string $group, \Closure $callback): mixed
    {
        return Cache::remember(
            self::key($group),
            (int) config('seo.sitemap.cache_ttl', 21600),
            $callback
        );
    }
}
