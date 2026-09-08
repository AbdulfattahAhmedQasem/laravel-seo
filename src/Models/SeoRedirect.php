<?php

namespace IProfile\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $from
 * @property string $to
 * @property int    $status
 * @property int    $hits
 */
class SeoRedirect extends Model
{
    /**
     * عدّاد إصدار الكاش. لا نستخدم Cache::tags لأن ملفات ومصفوفات
     * لارفل لا تدعم الوسوم — وربط الحزمة بـ Redis وحده قيد لا مبرر له.
     * زيادة العدّاد تُبطل كل مفاتيح المسارات دفعة واحدة بلا مسح.
     */
    public const VERSION_KEY = 'seo.redirects.version';

    protected $table = 'seo_redirects';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => 'integer', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $redirect) {
            $redirect->from = self::normalize($redirect->from);
            $redirect->to = self::isExternal($redirect->to)
                ? $redirect->to
                : self::normalize($redirect->to);

            $redirect->guardAgainstLoops();
        });

        static::saved(function (self $redirect) {
            $redirect->flattenIncomingChains();
            self::bumpVersion();
        });

        static::deleted(fn () => self::bumpVersion());
    }

    /* ---------------------------------------------------------------------
     | البحث
     |--------------------------------------------------------------------- */

    /**
     * يبحث عن تحويل واحد بمفتاحه المفهرس.
     *
     * التصميم السابق كان يحمّل الجدول كاملاً في مصفوفة واحدة ويفكّها من
     * الكاش عند كل 404 — مع 10 آلاف تحويل هذا فكّ تسلسل لعشرات الميجابايت
     * على طلب لصفحة غير موجودة. الآن: مفتاح كاش لكل مسار + استعلام limit 1.
     *
     * @return array{to:string,status:int}|null
     */
    public static function lookup(string $path): ?array
    {
        $path = self::normalize($path);
        $key = self::cacheKey($path);

        $cached = Cache::get($key);

        // "لا يوجد" يُخزَّن أيضاً: بدونه يستعلم كل 404 متكرر قاعدة البيانات.
        if ($cached === 'miss') {
            return null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        $row = static::query()
            ->where('from', $path)
            ->first(['to', 'status']);

        if ($row === null) {
            Cache::put($key, 'miss', (int) config('seo.redirects.miss_cache_ttl', 300));

            return null;
        }

        $result = ['to' => $row->to, 'status' => $row->status ?: 301];

        Cache::put($key, $result, (int) config('seo.redirects.cache_ttl', 3600));

        return $result;
    }

    protected static function cacheKey(string $path): string
    {
        return 'seo.redirect.'.self::version().'.'.sha1($path);
    }

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function bumpVersion(): void
    {
        // increment يفشل صامتاً إن لم يوجد المفتاح في بعض المخازن.
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /* ---------------------------------------------------------------------
     | سلامة البيانات
     |--------------------------------------------------------------------- */

    /**
     * حلقة التحويل تُنتج ERR_TOO_MANY_REDIRECTS للزائر وتُسقط الصفحة من
     * الفهرس. المنع عند الكتابة أرخص بكثير من الاكتشاف في الإنتاج.
     */
    protected function guardAgainstLoops(): void
    {
        if ($this->from === $this->to) {
            throw new \InvalidArgumentException(
                "تحويل يشير إلى نفسه: {$this->from}"
            );
        }

        if (self::isExternal($this->to)) {
            return;
        }

        $seen = [$this->from];
        $target = $this->to;

        // تتبّع السلسلة من الوجهة: إن عادت إلى المصدر فهي حلقة.
        for ($depth = 0; $depth < 25; $depth++) {
            if (in_array($target, $seen, true)) {
                throw new \InvalidArgumentException(
                    "حلقة تحويل: {$this->from} → {$this->to}"
                );
            }

            $seen[] = $target;

            $next = static::query()
                ->where('from', $target)
                ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
                ->value('to');

            if ($next === null) {
                return;
            }

            $target = $next;
        }

        throw new \InvalidArgumentException("سلسلة تحويل أعمق من 25 خطوة تبدأ من {$this->from}");
    }

    /**
     * أ→ب موجود، ثم أُنشئ ب→ج: يصبح أ→ج مباشرة.
     *
     * كل قفزة إضافية تُضعف إشارة الترتيب المنقولة وتضيف رحلة شبكة.
     * جوجل يتوقف عند 5 قفزات في الزحفة الواحدة.
     */
    protected function flattenIncomingChains(): void
    {
        static::query()
            ->where('to', $this->from)
            ->whereKeyNot($this->getKey())
            ->get()
            ->each(function (self $incoming) {
                if ($incoming->from === $this->to) {
                    return; // كان سيصبح تحويلاً إلى نفسه
                }

                $incoming->forceFill(['to' => $this->to])->saveQuietly();
            });
    }

    /* ---------------------------------------------------------------------
     | أدوات
     |--------------------------------------------------------------------- */

    /**
     * يختزل أي رابط إلى مسار مقارن.
     *
     * الرابط المطلق على نفس المضيف يُختزل إلى مساره: seoUrl() تعيد
     * رابطاً مطلقاً دائماً، وتخزينه كاملاً كان يجعل الميدل وير — الذي
     * يقارن بـ request()->path() — لا يطابقه أبداً.
     */
    public static function normalize(string $path): string
    {
        if (self::isExternal($path)) {
            return $path;
        }

        $parsed = parse_url($path, PHP_URL_PATH);

        return '/'.trim(is_string($parsed) ? $parsed : $path, '/');
    }

    /**
     * خارجي = مضيف مختلف عن مضيف التطبيق. مجرد بدئه بـ http لا يكفي.
     */
    public static function isExternal(string $target): bool
    {
        $host = parse_url($target, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        return ! in_array(mb_strtolower($host), self::localHosts(), true);
    }

    /**
     * @return array<int,string>
     */
    protected static function localHosts(): array
    {
        $hosts = [parse_url((string) config('app.url'), PHP_URL_HOST)];

        if (app()->bound('request')) {
            $hosts[] = request()->getHost();
        }

        return array_values(array_unique(array_map(
            fn ($host) => mb_strtolower((string) $host),
            array_filter($hosts, is_string(...))
        )));
    }
}
