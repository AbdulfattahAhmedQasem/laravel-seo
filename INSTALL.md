# دليل تثبيت وتشغيل حزمة `iprofile/laravel-seo`

> **v1.0.0** · تعمل مع **PHP 8.3+** و **Laravel 11 / 12 / 13**
> اقرأ هذا الملف كاملاً قبل التثبيت. الخطوات الإلزامية الثلاث في القسم ٤ — تجاوز أيٍّ منها يعني أن جزءاً من الحزمة لن يعمل.

---

## ١. ماذا تعطيك هذه الحزمة

- وسوم `<head>` كاملة من مكوّن واحد: `title`, `description`, `canonical`, `robots`, Open Graph, Twitter Cards, hreflang
- بيانات منظمة (Schema.org): Organization, WebSite + SearchAction, BreadcrumbList, FAQPage — **مؤمّنة ضد حقن `</script>`**
- خريطة موقع ديناميكية **متدفقة** (ذاكرة ثابتة عند ٥٠ ألف صفحة) بفهرس وتقسيم تلقائي
- `robots.txt` يمنع فهرسة بيئات التطوير تلقائياً
- مدير تحويلات 301/410 مع إنشاء تلقائي عند تغيير الـ slug، وكشف الحلقات، وتسطيح السلاسل
- تنظيف `canonical` من معاملات التتبّع (`utm_*`, `fbclid`, ...)
- `SeoScore` لتحليل المحتوى في لوحة التحكم

---

## ٢. فك الضغط في المكان الصحيح

من **جذر المشروع** الذي تريد تثبيتها فيه:

```bash
cd /path/to/your-project
mkdir -p packages
unzip -q iprofile-laravel-seo-v1.0.0.zip -d packages/
```

النتيجة المطلوبة **بالضبط**:

```
packages/laravel-seo/composer.json
packages/laravel-seo/src/SeoServiceProvider.php
packages/laravel-seo/config/seo.php
```

تحقّق:

```bash
test -f packages/laravel-seo/composer.json && echo OK || echo "المسار خاطئ"
```

> إن نتج مجلد متداخل زائد مثل `packages/laravel-seo/laravel-seo/` فصحّحه بالنقل.

**قواعد صارمة:**
- ❌ لا تضعها داخل `vendor/` بأي حال — Composer سيمسحها
- ❌ لا تنسخ ملفاتها يدوياً إلى `app/` أو `config/`
- ✅ تبقى في `packages/` وتُربط عبر Composer

---

## ٣. الربط والتثبيت

### ٣.١ أضف المستودع المحلي

في `composer.json` **الخاص بمشروعك** (لا الحزمة):

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/laravel-seo",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "iprofile/laravel-seo": "@dev"
    }
}
```

```bash
composer update iprofile/laravel-seo -W
```

### ٣.٢ تأكّد أن الربط رمزي لا نسخة

```bash
ls -la vendor/iprofile/
```

يجب أن يظهر `laravel-seo -> ../../packages/laravel-seo`. إن ظهر مجلداً عادياً فتعديلاتك على الحزمة لن تنعكس.

### ٣.٣ شغّل المثبّت

```bash
php artisan seo:install
```

يسأل عن تشغيل المايجريشن — وافق. يجب أن يُنشأ:
- `config/seo.php`
- مايجريشن `create_seo_meta_table` و `create_seo_redirects_table`
- جدولا `seo_meta` و `seo_redirects`

تحقّق:

```bash
php artisan route:list | grep -i seo
```

يجب أن تظهر أربعة روطات: `seo.sitemap` · `seo.sitemap.page` · `seo.sitemap.group` · `seo.robots`

---

## ٤. الخطوات الإلزامية الثلاث ⚠️

### ٤.١ الميدل وير — **عالمياً لا في مجموعة `web`**

في `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(\IProfile\Seo\Http\Middleware\HandleSeoRedirects::class);
})
```

> **لماذا `append()` لا `appendToGroup('web', ...)`؟**
> مجموعات الروطات **لا تُنفَّذ إطلاقاً** على طلب لا يطابق أي روط — وهذا حال كل رابط قديم بطبيعته. داخل المجموعة لن يرى الميدل وير الـ 404 الذي وُجد لمعالجته، فتكون ميزة التحويلات كلها معطّلة بصمت.
> الكلفة صفر: يخرج فوراً ما لم تكن الاستجابة 404.

**للارفل 10 وما دونه** (`app/Http/Kernel.php`): أضفه إلى `protected $middleware` لا إلى `$middlewareGroups['web']`.

### ٤.٢ احذف `public/robots.txt`

```bash
rm public/robots.txt
```

> الملف الثابت يُخدَم قبل لارفل (في التطوير وفي nginx/apache معاً)، فيحجب روط الحزمة تماماً. ملف لارفل الافتراضي يحتوي `Disallow:` أي **سماح كامل بالفهرسة** — فتُفهرس بيئة التطوير.

تحقّق أن الروط يعمل:

```bash
curl -s http://127.0.0.1:8000/robots.txt
# في local يجب أن يعطي: Disallow: /
```

### ٤.٣ أضف المكوّن إلى اللاياوت

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-seo::meta />

    @vite(['resources/css/app.css'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

> **احذف أي `<title>` أو `<meta name="description">` قديم من اللاياوت** — وإلا تتكرر الوسوم ويرتبك جوجل.

> **تنبيه مسار:** إن استخدمت `<x-layouts.app>` فاللاياوت يجب أن يكون في `resources/views/components/layouts/app.blade.php` **لا** في `resources/views/layouts/`. غير ذلك ستحصل على `Unable to locate a class or view for component [layouts.app]`.

---

## ٥. تجهيز الموديلات

لكل موديل يمثّل صفحة عامة:

```php
use IProfile\Seo\Concerns\HasSeo;
use IProfile\Seo\Contracts\Seoable;

class Service extends Model implements Seoable
{
    use HasSeo;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // مطلوب — بدونه لا يعمل الـ sitemap ولا التحويل التلقائي
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

الموديل الآن:
- له علاقة `seo()` لتخزين بيانات مخصّصة
- يشتق title/description/image تلقائياً من حقوله إن لم تُملأ
- ينشئ تحويل 301 تلقائياً عند تغيير الـ slug

### ⚠️ تجنّب N+1 في صفحات القوائم

```php
$services = Service::withSeo()->published()->get();
```

بدون `withSeo()` كل استدعاء لـ `seoData()` داخل الحلقة = استعلام منفصل.

---

## ٦. الإعدادات

### `.env`

```env
SEO_TITLE_SUFFIX="اسم موقعك"
SEO_DEFAULT_DESCRIPTION="وصف افتراضي للصفحات التي لا تحدّد وصفها"
SEO_DEFAULT_IMAGE="/img/og-default.jpg"
SEO_ORG_NAME="الاسم التجاري"
SEO_ORG_LEGAL_NAME=
SEO_ORG_EMAIL=
SEO_ORG_PHONE=
SEO_ORG_LOGO="/img/logo.png"
SEO_TWITTER=
```

> **`SEO_DEFAULT_DESCRIPTION` ليس اختيارياً عملياً:** غيابه يُسقط بند `meta description` في Lighthouse. الحزمة **لا تخترع وصفاً** لأن الوصف المولّد آلياً أسوأ من غيابه في عين جوجل.

### `config/seo.php` — أهم المفاتيح

```php
// بيانات المنشأة (تُطبع كـ JSON-LD في كل صفحة)
'organization' => [
    'type'    => 'ProfessionalService',  // أو Organization / LocalBusiness
    'address' => ['street' => ..., 'city' => ..., 'country' => 'SA'],
    'geo'     => ['lat' => ..., 'lng' => ...],
    'social'  => ['https://twitter.com/...', 'https://linkedin.com/company/...'],
],

// أبعاد صورة المشاركة — اضبطها فقط إن كنت تضمن مقاس كل صورك
'og_image_size' => ['width' => 1200, 'height' => 630],

// خريطة الموقع
'sitemap' => [
    'groups' => [
        'services' => [
            'model'      => \App\Models\Service::class,
            'scope'      => 'published',
            'priority'   => '0.9',
            'changefreq' => 'monthly',
        ],
    ],
    'static_urls' => [
        ['loc' => '/',        'priority' => '1.0', 'changefreq' => 'weekly'],
        ['loc' => '/contact', 'priority' => '0.7'],
    ],
],

// الرابط المعياري — قائمة سماح لا منع
'canonical' => [
    'strip_query'   => true,
    'allowed_query' => ['page'],   // أضف معاملاتك المشروعة هنا
],
```

> **لماذا قائمة سماح؟** معاملات التتبّع لا حصر لها (`utm_*`, `fbclid`, `gclid`, وما يخترعه مُعلن غداً). أي قائمة منع تتقادم بصمت فتُنتج canonical مختلفاً لكل زيارة = محتوى مكرر في عين جوجل.

---

## ٧. الاستخدام اليومي

### الحد الأدنى

```php
public function show(Service $service)
{
    Seo::for($service);   // يشتق كل شيء من الموديل

    return view('services.show', compact('service'));
}
```

### التحكم الكامل

```php
use IProfile\Seo\Facades\Seo;

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
    ]);
```

> `Seo::for()` تعيد `SeoManager` فتبقى السلسلة متسقة. للوصول إلى كائن البيانات: `Seo::data()`.

### صفحة بلا موديل

```php
Seo::title('من نحن')
   ->description('شركتنا متخصصة في ...')
   ->crumb('الرئيسية', route('home'))
   ->crumb('من نحن', route('about'));
```

### الصفحات المرقّمة

```php
Seo::title('المدونة')->paginated($posts->currentPage(), $posts->lastPage());
```

canonical ذاتي يحمل `?page=N`، و`noindex` تلقائي للصفحة خارج النطاق.

### منع الفهرسة

```php
Seo::for($post)->noindex();        // noindex, follow
Seo::for($post)->noindex(false);   // noindex, nofollow
```

### مكوّنات جاهزة في الفيو

```blade
{{-- مسار تنقل مرئي يطابق JSON-LD --}}
<x-seo::breadcrumbs class="mb-6 text-gray-500" />

{{-- الأسئلة الشائعة (Rich Snippet) --}}
<x-seo::schema.faq :items="[
    ['question' => 'كم تستغرق مدة التنفيذ؟', 'answer' => 'من 2 إلى 6 أسابيع.'],
]" />

{{-- مربع البحث في نتائج جوجل — الصفحة الرئيسية فقط --}}
<x-seo::schema.website search-url="{{ url('/search?q=') }}" />
```

### التحويلات

```php
use IProfile\Seo\Models\SeoRedirect;

SeoRedirect::create(['from' => '/old-page', 'to' => '/new-page']);
SeoRedirect::create(['from' => '/removed',  'to' => '/', 'status' => 410]);
```

- الحلقات (`أ→أ` و `أ→ب→أ`) **تُرفض** بـ `InvalidArgumentException`
- السلاسل تُسطَّح تلقائياً: إنشاء `ب→ج` يحوّل `أ→ب` القائم إلى `أ→ج`
- الأصول (صور، CSS) لا تستعلم قاعدة البيانات إطلاقاً
- `POST` على رابط قديم لا يفقد جسمه في تحويل 301

---

## ٨. الأوامر

```bash
php artisan seo:install    # نشر الإعدادات والمايجريشن وتشغيلها
php artisan seo:clear      # إبطال كاش التحويلات وخريطة الموقع
```

---

## ٩. قائمة تحقق قبل الإنتاج

```bash
php artisan optimize:clear
curl -s https://yoursite.com/ | grep -E '<title>|canonical|ld\+json'
curl -s https://yoursite.com/sitemap.xml
curl -s https://yoursite.com/robots.txt
```

- [ ] `<title>` يظهر ويحتوي لاحقة اسم الموقع
- [ ] `<meta name="description">` موجود ✅ (يسقط بند Lighthouse بدونه)
- [ ] `<link rel="canonical">` يشير للرابط الصحيح بلا معاملات تتبّع
- [ ] `<meta name="robots">` يحتوي `index, follow` **في الإنتاج فقط**
- [ ] بلوك JSON-LD لـ Organization
- [ ] `robots.txt` يعطي `Allow: /` ويشير إلى `Sitemap:` (وليس `Disallow: /`)
- [ ] `sitemap.xml` يعيد XML صالحاً ويسرد كل المجموعات
- [ ] `public/robots.txt` **محذوف**
- [ ] الميدل وير مسجّل بـ `append()` لا `appendToGroup()`
- [ ] `SEO_DEFAULT_DESCRIPTION` مضبوط
- [ ] صورة `og-default.jpg` موجودة بمقاس 1200×630
- [ ] `APP_ENV=production` (وإلا فرض `noindex` مقصود)

اختبر النتائج الغنية على: `https://search.google.com/test/rich-results`

---

## ١٠. الأخطاء الشائعة وحلولها

| العرَض | السبب | الحل |
|---|---|---|
| `Command "seo:install" is not defined` | المزوّد لم يُكتشف | `composer dump-autoload && php artisan package:discover` |
| `seo:install` يطبع DONE ولا يُنشر شيء | وسوم نشر خاطئة | نُسخة قديمة — تأكّد أنك على v1.0.0 |
| `Unable to locate ... component [seo::meta]` | فيوهات الحزمة غير مسجّلة | `php artisan view:clear` ثم `package:discover` |
| `Unable to locate ... component [layouts.app]` | مسار اللاياوت | انقله إلى `resources/views/components/layouts/` |
| `robots.txt` يعطي `Disallow:` فارغة | `public/robots.txt` يحجب الروط | احذف الملف (القسم ٤.٢) |
| التحويلات لا تعمل | الميدل وير في مجموعة `web` | سجّله بـ `append()` عالمياً (القسم ٤.١) |
| `sitemap.xml` فارغ | لا `static_urls` ولا مجموعات | عرّفها في `config/seo.php` |
| مجموعة لا تظهر في الفهرس | لا صفوف مطابقة للـ scope | مقصود — المجموعة الفارغة لا تُدرج |
| الصفحة `noindex` في الإنتاج | `APP_ENV` ليس `production` | صحّح `.env` أو `indexable_environments` |
| `Target class [IProfile\Seo\...] does not exist` | psr-4 | `composer dump-autoload` |

---

## ١١. تشغيل اختبارات الحزمة نفسها

```bash
cd packages/laravel-seo
composer install
composer check          # تنسيق + تحليل ساكن + اختبارات
```

أو منفصلة:

```bash
vendor/bin/pest                                  # 75 اختباراً
vendor/bin/phpstan analyse --memory-limit=1G     # Larastan مستوى 6
vendor/bin/pint --test                           # التنسيق
```

---

## ١٢. الترقية لاحقاً إلى مستودع مستقل

عندما تجهّز مستودع GitHub خاصاً، استبدل `path` بـ `vcs` في أي مشروع:

```json
"repositories": [{ "type": "vcs", "url": "git@github.com:<حسابك>/laravel-seo.git" }],
"require": { "iprofile/laravel-seo": "^1.0" }
```

Composer يقرأ وسوم Git كإصدارات:

```bash
git tag v1.0.0 && git push --tags
```

---

## ملفات مرجعية داخل الحزمة

| الملف | المحتوى |
|---|---|
| `README.md` | مرجع الاستخدام الكامل |
| `CHANGELOG.md` | سجل التغييرات وجدول التغييرات الكاسرة |
| `AUDIT.md` | تقرير المراجعة الهندسية: كل عيب وُجد، وكيف أُصلح، وما رُفض ولماذا |
