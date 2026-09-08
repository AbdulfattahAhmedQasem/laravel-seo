<?php

namespace IProfile\Seo\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use IProfile\Seo\Contracts\Seoable;

/**
 * يبني خرائط الموقع بالبثّ لا بالتجميع.
 *
 * التصميم السابق كان ->get()->map()->all() ثم يُخزَّن كاملاً في الكاش:
 * مع 50 ألف صفحة هذا تحميل كل الموديلات في الذاكرة دفعة واحدة، ثم
 * مصفوفة بعشرات الميجابايت في الكاش تُفكّ عند كل زحفة. هنا نُخرج
 * الروابط واحداً واحداً عبر مولّد (Generator)، فتبقى الذاكرة ثابتة
 * مهما كبر الموقع.
 */
final class SitemapGenerator
{
    public const STATIC_GROUP = 'static';

    /**
     * @return array<int,string> أسماء المجموعات المعرّفة فعلاً
     */
    public static function groups(): array
    {
        return array_keys((array) config('seo.sitemap.groups', []));
    }

    public static function hasGroup(string $group): bool
    {
        return $group === self::STATIC_GROUP
            || array_key_exists($group, (array) config('seo.sitemap.groups', []));
    }

    /**
     * البيانات الوصفية لكل مجموعة: عدد الروابط وآخر تعديل حقيقي.
     *
     * هذه وحدها ما يُخزَّن في الكاش — قيمتان لا ملف كامل.
     *
     * @return array{count:int, lastmod:?string, pages:int}
     */
    public static function meta(string $group): array
    {
        return SitemapCache::remember($group, function () use ($group) {
            if ($group === self::STATIC_GROUP) {
                $count = count((array) config('seo.sitemap.static_urls', []));

                return [
                    'count' => $count,
                    'lastmod' => self::staticLastmod(),
                    'pages' => self::pageCount($count),
                ];
            }

            $query = self::queryFor($group);

            if ($query === null) {
                return ['count' => 0, 'lastmod' => null, 'pages' => 0];
            }

            $count = (clone $query)->toBase()->getCountForPagination();
            $lastmod = self::maxUpdatedAt($query);

            return [
                'count' => $count,
                'lastmod' => $lastmod,
                'pages' => self::pageCount($count),
            ];
        });
    }

    public static function pageCount(int $count): int
    {
        $perFile = max((int) config('seo.sitemap.max_urls_per_file', 45000), 1);

        return (int) max(ceil($count / $perFile), 1);
    }

    /**
     * يبثّ روابط مجموعة، اختيارياً صفحة واحدة منها.
     *
     * @return \Generator<int,array{loc:string,lastmod:?string,changefreq:?string,priority:?string}>
     */
    public static function urls(string $group, ?int $page = null): \Generator
    {
        $perFile = max((int) config('seo.sitemap.max_urls_per_file', 45000), 1);
        $offset = $page === null ? 0 : ($page - 1) * $perFile;
        $limit = $page === null ? PHP_INT_MAX : $perFile;

        $emitted = 0;
        $skipped = 0;

        foreach (self::rawUrls($group) as $url) {
            if ($skipped < $offset) {
                $skipped++;

                continue;
            }

            if ($emitted >= $limit) {
                return;
            }

            $emitted++;

            yield $url;
        }
    }

    /**
     * @return \Generator<int,array<string,mixed>>
     */
    protected static function rawUrls(string $group): \Generator
    {
        if ($group === self::STATIC_GROUP) {
            foreach ((array) config('seo.sitemap.static_urls', []) as $entry) {
                if (empty($entry['loc'])) {
                    continue;
                }

                yield [
                    'loc' => CanonicalUrl::isAbsolute($entry['loc']) ? $entry['loc'] : url($entry['loc']),
                    'lastmod' => $entry['lastmod'] ?? null,
                    'changefreq' => $entry['changefreq'] ?? null,
                    'priority' => $entry['priority'] ?? null,
                ];
            }

            return;
        }

        $query = self::queryFor($group);

        if ($query === null) {
            return;
        }

        $config = (array) config("seo.sitemap.groups.{$group}");
        $chunk = max((int) config('seo.sitemap.chunk_size', 1000), 1);

        /*
         | lazyById يسحب دفعة، يُفرغها، ثم يسحب التالية بمفتاح أكبر.
         | لا offset (الذي يتباطأ خطياً)، ولا تحميل كامل في الذاكرة.
         */
        foreach ($query->lazyById($chunk) as $model) {
            $loc = self::urlFor($model);

            if ($loc === null) {
                continue;
            }

            yield [
                'loc' => $loc,
                'lastmod' => self::iso($model->updated_at ?? null),
                'changefreq' => $config['changefreq'] ?? null,
                'priority' => $config['priority'] ?? null,
            ];
        }
    }

    /**
     * الموديل الذي لا يعرّف seoUrl() يُتخطّى بدل أن يُدرَج برابط الصفحة
     * الرئيسية — التصميم السابق كان يُنتج 50 ألف مدخل كلها "/"، وهو
     * أسوأ من غياب الخريطة أصلاً.
     */
    protected static function urlFor(Model $model): ?string
    {
        if ($model instanceof Seoable || method_exists($model, 'seoUrl')) {
            $url = $model->seoUrl();

            return is_string($url) && $url !== '' ? $url : null;
        }

        return null;
    }

    protected static function queryFor(string $group): ?Builder
    {
        $config = (array) config("seo.sitemap.groups.{$group}");

        if (empty($config['model']) || ! class_exists($config['model'])) {
            return null;
        }

        $model = $config['model'];

        if (! is_subclass_of($model, Model::class)) {
            return null;
        }

        $query = $model::query();

        if (! empty($config['scope'])) {
            $scope = $config['scope'];

            if (! method_exists($model, 'scope'.ucfirst($scope))) {
                return null;
            }

            $query->{$scope}();
        }

        return $query;
    }

    protected static function maxUpdatedAt(Builder $query): ?string
    {
        $model = $query->getModel();

        if (! $model->usesTimestamps()) {
            return null;
        }

        return self::iso((clone $query)->max($model->getUpdatedAtColumn()));
    }

    protected static function staticLastmod(): ?string
    {
        $dates = array_filter(array_column((array) config('seo.sitemap.static_urls', []), 'lastmod'));

        return $dates === [] ? null : self::iso(max($dates));
    }

    protected static function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toAtomString();
        } catch (\Throwable) {
            return null;
        }
    }
}
