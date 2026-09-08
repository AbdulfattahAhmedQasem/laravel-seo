<?php

namespace IProfile\Seo\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use IProfile\Seo\Concerns\HasSeo;
use IProfile\Seo\Contracts\Seoable;

class Article extends Model implements Seoable
{
    use HasSeo;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function seoUrl(): string
    {
        return url('/articles/'.$this->slug);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}
