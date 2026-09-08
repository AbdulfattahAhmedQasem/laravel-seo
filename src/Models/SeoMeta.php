<?php

namespace IProfile\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|null $title
 * @property string|null $description
 * @property string|null $canonical
 * @property string|null $robots
 * @property string|null $og_image
 * @property array|null  $schema
 */
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['schema' => 'array'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
