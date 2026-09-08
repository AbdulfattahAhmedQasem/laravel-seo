# سجل التغييرات

## [1.0.0] - غير مُصدر

### أُضيف
- نظام Meta ديناميكي (`seo_meta`) متعدد الأشكال ومتعدد اللغات
- `HasSeo` trait مع اشتقاق تلقائي و 301 تلقائي عند تغيير الـ slug
- واجهة `Seo` السلسة و `SeoData`
- مكوّن `<x-seo::meta />` الشامل
- مكوّنات Schema: Organization, WebSite, BreadcrumbList, FAQPage
- Sitemap ديناميكي بفهرس + robots.txt
- middleware التحويلات 301
- أمرا `seo:install` و `seo:clear`
- `SeoScore` للتحليل في لوحة التحكم
