<?php

namespace IProfile\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoRedirect extends Model
{
    public const CACHE_KEY = 'seo.redirects.map';

    protected $table = 'seo_redirects';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return array<string,array{to:string,status:int}>
     */
    public static function map(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            config('seo.redirects.cache_ttl', 3600),
            fn () => static::query()
                ->get(['from', 'to', 'status'])
                ->mapWithKeys(fn ($r) => [$r->from => ['to' => $r->to, 'status' => (int) $r->status]])
                ->all()
        );
    }

    public static function normalize(string $path): string
    {
        return '/'.trim(parse_url($path, PHP_URL_PATH) ?? $path, '/');
    }
}
