# iprofile/laravel-seo

حزمة SEO كاملة للارفل — تعطيك ما تعطيه Yoast / Rank Math لووردبريس، لكن بتحكم كامل.

**تعمل مع Laravel 11 / 12 / 13 و PHP 8.3+**

---

## ماذا تتضمن

- نظام Meta ديناميكي مخزّن في قاعدة البيانات (polymorphic) مع اشتقاق تلقائي من المحتوى
- مكوّن Blade واحد يطبع كل شيء: title, description, canonical, robots, Open Graph, Twitter Cards, hreflang
- مكوّنات Schema.org جاهزة: Organization, WebSite+SearchAction, BreadcrumbList, FAQPage
- Sitemap ديناميكي بفهرس مقسّم + robots.txt (يمنع فهرسة بيئات الاختبار تلقائياً)
- مدير تحويلات 301 مع إنشاء تلقائي عند تغيير أي slug
- محلل SEO بسيط (SeoScore) لاستخدامه في لوحة التحكم

---

## التثبيت

### أثناء التطوير (مستودع محلي)

في `composer.json` للمشروع:

```json
{
    "repositories": [
        { "type": "path", "url": "../packages/laravel-seo", "options": { "symlink": true } }
    ],
    "require": {
        "iprofile/laravel-seo": "@dev"
    }
}
```

```bash
composer update iprofile/laravel-seo
```

مع `symlink: true` أي تعديل في الحزمة ينعكس فوراً على المشروع — لا حاجة لإعادة التثبيت.

### للإنتاج (مستودع GitHub خاص)

```json
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:your-org/laravel-seo.git" }
    ],
    "require": {
        "iprofile/laravel-seo": "^1.0"
    }
}
```

Composer يقرأ وسوم Git كإصدارات. أنشئ وسماً:

```bash
git tag v1.0.0 && git push --tags
```

على الخادم/CI أضف مفتاح SSH أو Personal Access Token لتتمكن Composer من السحب.

### ثم

```bash
php artisan seo:install
```

---

## الإعداد

### 1. أضف المكوّن إلى اللاياوت

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-seo::meta />

    @vite(['resources/css/app.css'])
</head>
```

### 2. سجّل middleware التحويلات

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\IProfile\Seo\Http\Middleware\HandleSeoRedirects::class);
})
```

> **يجب أن يكون عالمياً لا داخل مجموعة `web`.** الروابط القديمة لا تطابق أي روط،
> ومجموعة `web` لا تُنفَّذ إطلاقاً على الطلبات غير المطابقة — فلن يرى الميدل وير
> الـ 404 الذي يفترض أن يعالجه. الكلفة صفر: يخرج فوراً ما لم تكن الاستجابة 404.

### 3. جهّز موديلاتك

```php
use IProfile\Seo\Concerns\HasSeo;

class Service extends Model
{
    use HasSeo;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // مطلوب: يخبر الحزمة أين تعيش هذه الصفحة
    public function seoUrl(): string
    {
        return route('services.show', $this);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}
```

هذا كل شيء. الموديل الآن:
- له علاقة `seo()` لتخزين البيانات المخصصة
- يولّد title/description تلقائياً من حقوله إن لم تُملأ
- ينشئ تحويل 301 تلقائياً عند تغيير الـ slug

---

## الاستخدام

### الحد الأدنى — لا شيء إطلاقاً

```php
public function show(Service $service)
{
    Seo::for($service);

    return view('services.show', compact('service'));
}
```

الحزمة تشتق كل شيء من الموديل.

### التحكم الكامل

```php
use IProfile\Seo\Facades\Seo;

public function show(Post $post)
{
    Seo::for($post)
        ->article($post->published_at, $post->updated_at, $post->author->name)
        ->crumb('الرئيسية', route('home'))
        ->crumb('المدونة', route('blog.index'))
        ->crumb($post->title, $post->seoUrl())
        ->alternates([
            'ar' => route('blog.show', ['locale' => 'ar', 'post' => $post]),
            'en' => route('blog.show', ['locale' => 'en', 'post' => $post]),
        ])
        ->schema([
            '@context' => 'https://schema.org',
            '@type'    => 'BlogPosting',
            'headline' => $post->title,
            'datePublished' => $post->published_at->toIso8601String(),
            'author'   => ['@type' => 'Person', 'name' => $post->author->name],
        ]);

    return view('blog.show', compact('post'));
}
```

### صفحة بلا موديل

```php
Seo::title('من نحن')
   ->description('شركة اي بروفايل متخصصة في ...')
   ->crumb('الرئيسية', route('home'))
   ->crumb('من نحن', route('about'));
```

### منع الفهرسة

```php
Seo::for($post)->noindex();          // noindex, follow
Seo::for($post)->noindex(false);     // noindex, nofollow
```

### مسار تنقل مرئي

```blade
<x-seo::breadcrumbs class="mb-6 text-gray-500" />
```

يقرأ نفس البيانات المستخدمة في JSON-LD — فيتطابق ما يراه المستخدم مع ما يُصرَّح به لجوجل.

### الأسئلة الشائعة (Rich Snippet)

```blade
<x-seo::schema.faq :items="[
    ['question' => 'كم تستغرق مدة التنفيذ؟', 'answer' => 'من 2 إلى 6 أسابيع.'],
    ['question' => 'هل تقدمون صيانة؟',       'answer' => 'نعم، عقود سنوية.'],
]" />
```

### مربع البحث في نتائج جوجل

في الصفحة الرئيسية فقط:

```blade
<x-seo::schema.website search-url="{{ url('/search?q=') }}" />
```

---

## خريطة الموقع

عرّف المجموعات في `config/seo.php`:

```php
'sitemap' => [
    'enabled' => true,
    'groups' => [
        'services' => [
            'model' => \App\Models\Service::class,
            'scope' => 'published',
            'priority' => '0.9',
            'changefreq' => 'monthly',
        ],
        'blog' => [
            'model' => \App\Models\Post::class,
            'scope' => 'published',
            'priority' => '0.8',
            'changefreq' => 'weekly',
        ],
    ],
    'static_urls' => [
        ['loc' => '/',        'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => '/contact', 'priority' => '0.7', 'changefreq' => 'yearly'],
    ],
],
```

النتيجة:

```
/sitemap.xml            → فهرس
/sitemap-static.xml
/sitemap-services.xml
/sitemap-blog.xml
/robots.txt             → يشير إلى الفهرس تلقائياً
```

مُخزّن في الكاش 6 ساعات. لمسحه فوراً:

```bash
php artisan seo:clear
```

أو تلقائياً عبر Observer:

```php
Service::saved(fn () => Cache::forget('seo.sitemap.services'));
```

---

## تكامل Filament

```php
use Filament\Forms\Components\{Section, TextInput, Textarea, Select, FileUpload};

Section::make('إعدادات محركات البحث')
    ->collapsed()
    ->relationship('seo')
    ->schema([
        TextInput::make('title')->label('العنوان')->maxLength(60)
            ->live(debounce: 400)
            ->helperText(fn ($state) => mb_strlen($state ?? '').'/60'),

        Textarea::make('description')->label('الوصف')->rows(3)->maxLength(160)
            ->live(debounce: 400)
            ->helperText(fn ($state) => mb_strlen($state ?? '').'/160'),

        TextInput::make('focus_keyword')->label('الكلمة المفتاحية'),

        FileUpload::make('og_image')->label('صورة المشاركة (1200×630)')
            ->image()->imageResizeTargetWidth(1200)->imageResizeTargetHeight(630),

        Select::make('robots')->label('الفهرسة')->options([
            'index, follow, max-image-preview:large' => 'مفهرسة',
            'noindex, follow'   => 'غير مفهرسة، تتبع الروابط',
            'noindex, nofollow' => 'غير مفهرسة نهائياً',
        ]),

        TextInput::make('canonical')->label('الرابط المعياري')->url(),
    ]);
```

> انتبه: عند استخدام `->relationship('seo')` يجب ضبط `locale` عند الحفظ.
> أضف `->mutateRelationshipDataBeforeCreateUsing(fn ($data) => $data + ['locale' => app()->getLocale()])`.

للتحليل الحي:

```php
use IProfile\Seo\Support\SeoScore;

$result = SeoScore::analyze($title, $description, $keyword, $body, $slug);
// ['score' => 83, 'checks' => [...]]
```

---

## الاختبار

```bash
composer install
composer test
```

---

## الترخيص

MIT
