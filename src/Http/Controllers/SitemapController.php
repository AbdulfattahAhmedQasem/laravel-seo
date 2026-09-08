<?php

namespace IProfile\Seo\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SitemapController
{
    public function index(): Response
    {
        abort_unless(config('seo.sitemap.enabled', true), 404);

        $groups = array_keys(config('seo.sitemap.groups', []));

        // إن لم تُعرَّف مجموعات، أخرج ملفاً واحداً بدل الفهرس
        if (empty($groups)) {
            return $this->group('static');
        }

        return response()
            ->view('seo::sitemap.index', [
                'groups' => array_merge(['static'], $groups),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function group(string $group): Response
    {
        abort_unless(config('seo.sitemap.enabled', true), 404);

        $urls = Cache::remember(
            "seo.sitemap.{$group}",
            config('seo.sitemap.cache_ttl', 21600),
            fn () => $this->urlsFor($group)
        );

        abort_if($urls === null, 404);

        return response()
            ->view('seo::sitemap.urls', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        abort_unless(config('seo.robots.enabled', true), 404);

        $indexable = in_array(app()->environment(), config('seo.indexable_environments', ['production']), true);

        if (! $indexable) {
            $body = "User-agent: *\nDisallow: /";
        } else {
            $lines = ['User-agent: *', 'Allow: /'];

            foreach (config('seo.robots.disallow', []) as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            if (config('seo.sitemap.enabled', true)) {
                $lines[] = '';
                $lines[] = 'Sitemap: '.url(config('seo.sitemap.route', '/sitemap.xml'));
            }

            $body = implode("\n", $lines);
        }

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @return array<int,array<string,string>>|null
     */
    protected function urlsFor(string $group): ?array
    {
        if ($group === 'static') {
            return collect(config('seo.sitemap.static_urls', []))
                ->map(fn ($u) => [
                    'loc'        => Str::startsWith($u['loc'], 'http') ? $u['loc'] : url($u['loc']),
                    'lastmod'    => $u['lastmod'] ?? null,
                    'priority'   => $u['priority'] ?? '0.5',
                    'changefreq' => $u['changefreq'] ?? 'monthly',
                ])->all();
        }

        $config = config("seo.sitemap.groups.{$group}");

        if (! $config || ! class_exists($config['model'])) {
            return null;
        }

        $query = $config['model']::query();

        if (! empty($config['scope'])) {
            $query->{$config['scope']}();
        }

        return $query->get()->map(fn ($model) => [
            'loc'        => method_exists($model, 'seoUrl') ? $model->seoUrl() : url('/'),
            'lastmod'    => optional($model->updated_at)->toAtomString(),
            'priority'   => $config['priority'] ?? '0.7',
            'changefreq' => $config['changefreq'] ?? 'monthly',
        ])->all();
    }
}
