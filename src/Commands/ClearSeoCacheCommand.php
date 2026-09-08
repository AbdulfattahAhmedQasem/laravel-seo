<?php

namespace IProfile\Seo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use IProfile\Seo\Models\SeoRedirect;

class ClearSeoCacheCommand extends Command
{
    protected $signature = 'seo:clear';

    protected $description = 'مسح كاش خريطة الموقع والتحويلات';

    public function handle(): int
    {
        Cache::forget(SeoRedirect::CACHE_KEY);

        foreach (array_merge(['static'], array_keys(config('seo.sitemap.groups', []))) as $group) {
            Cache::forget("seo.sitemap.{$group}");
        }

        $this->components->info('تم مسح كاش الـ SEO.');

        return self::SUCCESS;
    }
}
