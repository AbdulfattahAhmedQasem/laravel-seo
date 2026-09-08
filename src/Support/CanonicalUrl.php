<?php

namespace IProfile\Seo\Support;

use Illuminate\Support\Str;

/**
 * يبني رابطاً معيارياً مستقراً.
 *
 * لماذا قائمة سماح لا قائمة منع: معاملات التتبّع لا حصر لها
 * (utm_*، fbclid، gclid، msclkid، mc_cid، igshid، ttclid، وكل ما يخترعه
 * مُعلن غداً). أي قائمة منع تتقادم بصمت وتنتج canonical مختلفاً لكل زيارة
 * — أي محتوى مكرر في عين جوجل. قائمة السماح تفشل في الاتجاه الآمن.
 */
final class CanonicalUrl
{
    public static function for(?string $url = null): string
    {
        $url ??= url()->current().self::currentQueryString();

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = mb_strtolower($parts['host']);

        // المنفذ الافتراضي لا يُكتب: https://site.com و https://site.com:443 مكرران.
        $port = isset($parts['port']) && ! self::isDefaultPort($scheme, $parts['port'])
            ? ':'.$parts['port']
            : '';

        $path = self::normalizePath($parts['path'] ?? '/');
        $query = self::filterQuery($parts['query'] ?? '');

        return $scheme.'://'.$host.$port.$path.($query === '' ? '' : '?'.$query);
    }

    protected static function currentQueryString(): string
    {
        $query = request()?->getQueryString();

        return $query ? '?'.$query : '';
    }

    protected static function isDefaultPort(string $scheme, int|string $port): bool
    {
        return ($scheme === 'https' && (int) $port === 443)
            || ($scheme === 'http' && (int) $port === 80);
    }

    protected static function normalizePath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');

            if (config('seo.canonical.trailing_slash', false)) {
                $path .= '/';
            }
        }

        return $path === '' ? '/' : $path;
    }

    protected static function filterQuery(string $query): string
    {
        if ($query === '' || ! config('seo.canonical.strip_query', true)) {
            return $query;
        }

        parse_str($query, $params);

        $allowed = config('seo.canonical.allowed_query', ['page']);

        $kept = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        // ترتيب ثابت: ?page=2&sort=a و ?sort=a&page=2 يجب أن ينتجا canonical واحداً.
        ksort($kept);

        return http_build_query($kept);
    }

    /**
     * يستخرج رقم الصفحة الحالي لدعم الترقيم.
     */
    public static function currentPage(): int
    {
        $page = (int) (request()?->query(config('seo.canonical.page_parameter', 'page')) ?? 1);

        return max($page, 1);
    }

    public static function isAbsolute(?string $url): bool
    {
        return $url !== null && Str::startsWith($url, ['http://', 'https://']);
    }
}
