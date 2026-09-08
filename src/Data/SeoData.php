<?php

namespace IProfile\Seo\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class SeoData implements Arrayable
{
    /** @var array<string,string> */
    public array $alternates = [];

    /** @var array<int,array{name:string,url:string}> */
    public array $breadcrumbs = [];

    /** @var array<int,array<string,mixed>> */
    public array $schemas = [];

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $image = null,
        public string $type = 'website',
        public ?string $keyword = null,
        public mixed $publishedAt = null,
        public mixed $updatedAt = null,
        public ?string $author = null,
    ) {}

    public static function make(?string $title = null, ?string $description = null): static
    {
        return new static(title: $title, description: $description);
    }

    /* ---------------------------------------------------------------------
     | واجهة سلسة (fluent)
     |--------------------------------------------------------------------- */

    public function title(?string $value): static
    {
        $this->title = $value;

        return $this;
    }

    public function description(?string $value, int $limit = 160): static
    {
        $this->description = $value ? Str::limit(self::squish($value), $limit, '') : null;

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function robots(string $value): static
    {
        $this->robots = $value;

        return $this;
    }

    public function noindex(bool $followLinks = true): static
    {
        $this->robots = 'noindex, '.($followLinks ? 'follow' : 'nofollow');

        return $this;
    }

    public function image(?string $url): static
    {
        $this->image = $url;

        return $this;
    }

    public function type(string $value): static
    {
        $this->type = $value;

        return $this;
    }

    public function article(mixed $publishedAt = null, mixed $updatedAt = null, ?string $author = null): static
    {
        $this->type = 'article';
        $this->publishedAt = $publishedAt;
        $this->updatedAt = $updatedAt;
        $this->author = $author;

        return $this;
    }

    /** @param array<int,array{name:string,url:string}> $items */
    public function breadcrumbs(array $items): static
    {
        $this->breadcrumbs = $items;

        return $this;
    }

    public function crumb(string $name, string $url): static
    {
        $this->breadcrumbs[] = compact('name', 'url');

        return $this;
    }

    /** @param array<string,string> $map locale => url */
    public function alternates(array $map): static
    {
        $this->alternates = $map;

        return $this;
    }

    /** @param array<string,mixed> $schema */
    public function schema(array $schema): static
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /* ---------------------------------------------------------------------
     | مُخرجات
     |--------------------------------------------------------------------- */

    public function fullTitle(): string
    {
        $title = $this->title ?: config('seo.default_title');
        $suffix = config('seo.title_suffix');

        if (! $suffix || $title === $suffix || str_contains($title, $suffix)) {
            return $title;
        }

        return $title.config('seo.title_separator', ' | ').$suffix;
    }

    public function resolvedDescription(): ?string
    {
        return $this->description ?: config('seo.default_description');
    }

    public function resolvedCanonical(): string
    {
        return $this->canonical ?: url()->current();
    }

    public function resolvedImage(): ?string
    {
        $image = $this->image ?: config('seo.default_image');

        if (! $image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
    }

    /**
     * يفرض noindex خارج بيئات الإنتاج — يمنع فهرسة staging بالخطأ.
     */
    public function resolvedRobots(): string
    {
        if (! in_array(app()->environment(), config('seo.indexable_environments', ['production']), true)) {
            return 'noindex, nofollow';
        }

        return $this->robots ?: config('seo.default_robots');
    }

    public function toArray(): array
    {
        return [
            'title'       => $this->fullTitle(),
            'description' => $this->resolvedDescription(),
            'canonical'   => $this->resolvedCanonical(),
            'robots'      => $this->resolvedRobots(),
            'image'       => $this->resolvedImage(),
            'type'        => $this->type,
        ];
    }

    protected static function squish(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }
}
