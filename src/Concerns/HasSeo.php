<?php

namespace IProfile\Seo\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use IProfile\Seo\Data\SeoData;
use IProfile\Seo\Models\SeoMeta;
use IProfile\Seo\Models\SeoRedirect;

/**
 * أضف هذا الـ trait إلى أي موديل يظهر كصفحة عامة.
 *
 * يمكن للموديل تخصيص السلوك بتعريف:
 *   - seoTitleFrom(): string
 *   - seoDescriptionFrom(): ?string
 *   - seoImageFrom(): ?string
 *   - seoUrl(): string
 */
trait HasSeo
{
    public static function bootHasSeo(): void
    {
        // 301 تلقائي عند تغيير الـ slug — يحفظ ترتيبك في جوجل
        static::updating(function ($model) {
            if (! config('seo.redirects.auto_on_slug_change', true)) {
                return;
            }

            if (! $model->isDirty('slug') || ! method_exists($model, 'seoUrl')) {
                return;
            }

            $original = $model->getOriginal('slug');
            $clone = (clone $model)->setAttribute('slug', $original);

            $from = SeoRedirect::normalize($clone->seoUrl());
            $to = SeoRedirect::normalize($model->seoUrl());

            if ($from !== $to) {
                SeoRedirect::updateOrCreate(['from' => $from], ['to' => $to, 'status' => 301]);
            }
        });

        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            $model->seo()->delete();
        });
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable')
            ->where('locale', app()->getLocale());
    }

    public function seoAllLocales(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * يبني SeoData كاملاً: يأخذ من قاعدة البيانات إن وُجد،
     * وإلا يشتقه من المحتوى — فلا تُنشر صفحة بلا وصف أبداً.
     */
    public function seoData(): SeoData
    {
        $meta = $this->relationLoaded('seo') ? $this->seo : $this->seo()->first();

        $data = new SeoData(
            title:       $meta?->title ?: $this->seoTitleFrom(),
            description: $meta?->description ?: $this->seoDescriptionFrom(),
            canonical:   $meta?->canonical ?: (method_exists($this, 'seoUrl') ? $this->seoUrl() : url()->current()),
            robots:      $meta?->robots,
            image:       $meta?->og_image ?: $this->seoImageFrom(),
            type:        $meta?->og_type ?: 'website',
            keyword:     $meta?->focus_keyword,
            publishedAt: $this->published_at ?? $this->created_at ?? null,
            updatedAt:   $this->updated_at ?? null,
        );

        if ($meta?->schema) {
            $data->schema($meta->schema);
        }

        return $data;
    }

    /* ---------------------------------------------------------------------
     | مصادر احتياطية — أعِد تعريفها في الموديل عند الحاجة
     |--------------------------------------------------------------------- */

    protected function seoTitleFrom(): string
    {
        foreach (['seo_title', 'title', 'name', 'heading'] as $key) {
            if (! empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return config('seo.default_title');
    }

    protected function seoDescriptionFrom(): ?string
    {
        foreach (['excerpt', 'summary', 'intro', 'description', 'body', 'content'] as $key) {
            if (! empty($this->{$key})) {
                $clean = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $this->{$key})) ?? '');

                return Str::limit($clean, 155, '');
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
