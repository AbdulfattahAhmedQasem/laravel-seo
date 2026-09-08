<?php

namespace IProfile\Seo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \IProfile\Seo\Data\SeoData data()
 * @method static \IProfile\Seo\Data\SeoData for(\Illuminate\Database\Eloquent\Model $model)
 * @method static \IProfile\Seo\SeoManager set(\IProfile\Seo\Data\SeoData $data)
 * @method static \IProfile\Seo\SeoManager title(?string $value)
 * @method static \IProfile\Seo\SeoManager description(?string $value, int $limit = 160)
 * @method static \IProfile\Seo\SeoManager canonical(?string $url)
 * @method static \IProfile\Seo\SeoManager robots(string $value)
 * @method static \IProfile\Seo\SeoManager noindex(bool $followLinks = true)
 * @method static \IProfile\Seo\SeoManager image(?string $url)
 * @method static \IProfile\Seo\SeoManager type(string $value)
 * @method static \IProfile\Seo\SeoManager article(mixed $publishedAt = null, mixed $updatedAt = null, ?string $author = null)
 * @method static \IProfile\Seo\SeoManager breadcrumbs(array $items)
 * @method static \IProfile\Seo\SeoManager crumb(string $name, string $url)
 * @method static \IProfile\Seo\SeoManager alternates(array $map)
 * @method static \IProfile\Seo\SeoManager schema(array $schema)
 *
 * @see \IProfile\Seo\SeoManager
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \IProfile\Seo\SeoManager::class;
    }
}
