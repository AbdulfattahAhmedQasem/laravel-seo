<?php

namespace IProfile\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical
 * @property string|null $robots
 * @property string|null $og_image
 * @property string|null $og_type
 * @property string|null $focus_keyword
 * @property array<string,mixed>|null $schema
 * @property string $locale
 */
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['schema' => 'array'];
    }

    protected static function booted(): void
    {
        // اللغة إلزامية للمفتاح الفريد. تركها للمستخدم كان يعني صفوفاً
        // بلغة افتراضية خاطئة في كل لوحة تحكم متعددة اللغات.
        static::creating(function (self $meta) {
            $meta->locale = $meta->locale ?: app()->getLocale();
        });
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeForLocale(Builder $query, ?string $locale = null): Builder
    {
        return $query->where('locale', $locale ?: app()->getLocale());
    }
}
