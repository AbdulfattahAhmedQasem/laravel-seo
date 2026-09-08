<?php

namespace IProfile\Seo\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use IProfile\Seo\Models\SeoRedirect;
use IProfile\Seo\Support\SitemapCache;

class ClearSeoCacheCommand extends Command
{
    protected $signature = 'seo:clear';

    protected $description = 'مسح كاش خريطة الموقع والتحويلات';

    public function handle(): int
    {
        // زيادة العدّاد تُبطل كل مفاتيح التحويلات دفعة واحدة بلا مسح
        // لكل مفتاح — يعمل على أي مخزن كاش بلا حاجة إلى الوسوم.
        SeoRedirect::bumpVersion();
        $this->components->task('إبطال كاش التحويلات');

        SitemapCache::flush();
        $this->components->task('مسح كاش خريطة الموقع');

        return self::SUCCESS;
    }
}
