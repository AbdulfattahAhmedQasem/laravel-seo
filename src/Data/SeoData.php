<?php

namespace IProfile\Seo\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use IProfile\Seo\Support\CanonicalUrl;

/**
 * @implements Arrayable<string,mixed>
 */
class SeoData implements Arrayable
{
    /** @var array<string,string> locale => url */
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

    public static function make(?string $title = null, ?string $description = null): self
    {
        return new self(title: $title, description: $description);
    }

    /* ---------------------------------------------------------------------
     | واجهة سلسة (fluent)
     |--------------------------------------------------------------------- */

    public function title(?string $value): static
    {
        $this->title = $value === null ? null : self::squish($value);

        return $this;
    }

    public function description(?string $value, ?int $limit = null): static
    {
        $limit ??= (int) config('seo.limits.description.max', 160);

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

    /**
     * صفحة مرقّمة: canonical ذاتي يحمل رقم الصفحة، و noindex للصفحات الفارغة.
     *
     * جوجل ألغى rel=prev/next عام 2019، والتوصية الحالية هي canonical ذاتي
     * لكل صفحة — لا canonical يشير إلى الصفحة الأولى، فذلك يُخفي المحتوى
     * العميق عن الفهرسة تماماً.
     */
    public function paginated(int $currentPage, int $lastPage): static
    {
        if ($currentPage > 1 && $currentPage > $lastPage) {
            $this->noindex();
        }

        if ($currentPage > 1 && $this->title !== null) {
            $this->title .= ' — '.__('صفحة :page', ['page' => $currentPage]);
        }

        return $this;
    }

    /* ---------------------------------------------------------------------
     | مُخرجات
     |--------------------------------------------------------------------- */

    public function siteName(): string
    {
        return (string) (config('seo.title_suffix') ?: config('app.name') ?: '');
    }

    /**
     * العنوان بلا لاحقة — يُستخدم في og:title و twitter:title، إذ تكرار
     * اسم الموقع فيهما يهدر المساحة المرئية في بطاقة المشاركة.
     */
    public function resolvedTitle(): string
    {
        return (string) ($this->title ?: config('seo.default_title') ?: $this->siteName());
    }

    public function fullTitle(): string
    {
        $title = $this->resolvedTitle();
        $suffix = $this->siteName();

        if ($suffix === '' || $title === '' || $title === $suffix || str_contains($title, $suffix)) {
            return $title === '' ? $suffix : $title;
        }

        return $title.config('seo.title_separator', ' | ').$suffix;
    }

    public function resolvedDescription(): ?string
    {
        return $this->description ?: (config('seo.default_description') ?: null);
    }

    public function resolvedCanonical(): string
    {
        return CanonicalUrl::for($this->canonical);
    }

    public function resolvedImage(): ?string
    {
        $image = $this->image ?: config('seo.default_image');

        if (! $image) {
            return null;
        }

        return CanonicalUrl::isAbsolute($image) ? $image : asset($image);
    }

    /**
     * يفرض noindex خارج بيئات الإنتاج — يمنع فهرسة staging بالخطأ.
     */
    public function resolvedRobots(): string
    {
        if (! $this->environmentIsIndexable()) {
            return 'noindex, nofollow';
        }

        return $this->robots ?: (string) config(
            'seo.default_robots',
            'index, follow, max-image-preview:large, max-snippet:-1'
        );
    }

    public function environmentIsIndexable(): bool
    {
        return in_array(
            app()->environment(),
            (array) config('seo.indexable_environments', ['production']),
            true
        );
    }

    public function openGraphLocale(): string
    {
        $locale = app()->getLocale();
        $mapped = config('seo.hreflang.map.'.$locale, $locale);

        return str_replace('-', '_', (string) $mapped);
    }

    public function publishedAtIso(): ?string
    {
        return self::toIso($this->publishedAt);
    }

    public function updatedAtIso(): ?string
    {
        return self::toIso($this->updatedAt);
    }

    /**
     * تاريخ غير قابل للتحليل لا يجوز أن يُسقط الصفحة كاملة — يُتجاهل الوسم.
     */
    protected static function toIso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->fullTitle(),
            'description' => $this->resolvedDescription(),
            'canonical' => $this->resolvedCanonical(),
            'robots' => $this->resolvedRobots(),
            'image' => $this->resolvedImage(),
            'type' => $this->type,
        ];
    }

    protected static function squish(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
    }
}
