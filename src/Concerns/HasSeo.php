<?php

namespace IProfile\Seo\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use IProfile\Seo\Data\SeoData;
use IProfile\Seo\Models\SeoMeta;
use IProfile\Seo\Models\SeoRedirect;

/**
 * أضف هذا الـ trait إلى أي موديل يظهر كصفحة عامة، ونفّذ عقد
 * IProfile\Seo\Contracts\Seoable.
 *
 * يمكن للموديل تخصيص السلوك بتعريف:
 *   - seoTitleFrom(): ?string
 *   - seoDescriptionFrom(): ?string
 *   - seoImageFrom(): ?string
 *   - seoSlugColumn(): string   (افتراضياً 'slug')
 *
 * @property-read \IProfile\Seo\Models\SeoMeta|null $seo
 */
trait HasSeo
{
    public static function bootHasSeo(): void
    {
        static::updating(function ($model) {
            $model->recordSlugRedirect();
        });

        static::deleting(function ($model) {
            // الحذف الناعم يُبقي الصفحة قابلة للاستعادة، فتُبقى بياناتها.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->seoAllLocales()->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | العلاقات
     |--------------------------------------------------------------------- */

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable')
            ->where('locale', app()->getLocale());
    }

    /**
     * كل اللغات — للحذف وللوحة التحكم متعددة اللغات.
     */
    public function seoAllLocales(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * التحميل المسبق. بدونه كل استدعاء seoData() في حلقة = استعلام
     * منفصل — وهو N+1 الكلاسيكي في صفحة قائمة بها 50 عنصراً.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithSeo(Builder $query): Builder
    {
        return $query->with('seo');
    }

    /* ---------------------------------------------------------------------
     | بناء البيانات
     |--------------------------------------------------------------------- */

    /**
     * يبني SeoData كاملاً: يأخذ من قاعدة البيانات إن وُجد، وإلا يشتقه
     * من المحتوى — فلا تُنشر صفحة بلا وصف أبداً.
     */
    public function seoData(): SeoData
    {
        $meta = $this->relationLoaded('seo') ? $this->getRelation('seo') : $this->seo()->first();

        $data = new SeoData(
            title: $meta?->title ?: $this->seoTitleFrom(),
            description: null,
            canonical: $meta?->canonical ?: $this->seoCanonical(),
            robots: $meta?->robots,
            image: $meta?->og_image ?: $this->seoImageFrom(),
            type: $meta?->og_type ?: 'website',
            keyword: $meta?->focus_keyword,
            publishedAt: $this->published_at ?? $this->created_at ?? null,
            updatedAt: $this->updated_at ?? null,
        );

        // عبر الدالة السلسة ليُطبَّق التنظيف والقصّ بحد الإعدادات الواحد.
        $data->description($meta?->description ?: $this->seoDescriptionFrom());

        if ($meta?->schema) {
            $data->schema($meta->schema);
        }

        return $data;
    }

    protected function seoCanonical(): ?string
    {
        return method_exists($this, 'seoUrl') ? $this->seoUrl() : null;
    }

    /* ---------------------------------------------------------------------
     | التحويل التلقائي عند تغيير الـ slug
     |--------------------------------------------------------------------- */

    public function seoSlugColumn(): string
    {
        return 'slug';
    }

    protected function recordSlugRedirect(): void
    {
        if (! config('seo.redirects.auto_on_slug_change', true)) {
            return;
        }

        $column = $this->seoSlugColumn();

        if (! $this->isDirty($column) || ! method_exists($this, 'seoUrl')) {
            return;
        }

        /*
         | نسخة نظيفة بالسمات الأصلية الخام:
         |   - newInstance لا يطلق أي حدث (بخلاف newFromBuilder الذي
         |     يطلق retrieved، فيوقظ مستمعين لا علاقة لهم بالأمر)
         |   - setRawAttributes يتجاوز الـ mutators وذوات الآثار الجانبية
         |   - ولا تُشارك العلاقات المحمّلة مع الأصل، بخلاف clone
         */
        $original = $this->newInstance([], true)
            ->setRawAttributes($this->getRawOriginal(), true);

        $from = SeoRedirect::normalize($original->seoUrl());
        $to = SeoRedirect::normalize($this->seoUrl());

        if ($from === $to) {
            return;
        }

        try {
            SeoRedirect::updateOrCreate(['from' => $from], ['to' => $to, 'status' => 301]);
        } catch (\InvalidArgumentException $e) {
            // حلقة تحويل مكتشفة: تغيير الـ slug لا يجوز أن يُسقط الحفظ.
            report($e);
        }
    }

    /* ---------------------------------------------------------------------
     | مصادر احتياطية — أعِد تعريفها في الموديل عند الحاجة
     |--------------------------------------------------------------------- */

    protected function seoTitleFrom(): ?string
    {
        foreach (['seo_title', 'title', 'name', 'heading'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return null;
    }

    protected function seoDescriptionFrom(): ?string
    {
        foreach (['excerpt', 'summary', 'intro', 'description', 'body', 'content'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return null;
    }

    protected function seoImageFrom(): ?string
    {
        foreach (['og_image', 'cover_image', 'image', 'thumbnail'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return null;
    }
}
