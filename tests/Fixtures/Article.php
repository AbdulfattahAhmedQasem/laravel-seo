<?php

namespace IProfile\Seo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use IProfile\Seo\Concerns\HasSeo;
use IProfile\Seo\Contracts\Seoable;

/**
 * @property string $slug
 * @property string|null $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $cover_image
 * @property Carbon|null $published_at
 */
class Article extends Model implements Seoable
{
    use HasSeo;

    protected $guarded = [];

    protected $casts = ['published_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seoUrl(): string
    {
        return url('/articles/'.$this->slug);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }
}
