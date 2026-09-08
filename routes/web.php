<?php

use Illuminate\Support\Facades\Route;
use IProfile\Seo\Http\Controllers\SitemapController;

/*
 | بلا مجموعة web عمداً: هذه نقاط عامة للزواحف لا تحتاج جلسة ولا CSRF،
 | ووجود Set-Cookie عليها يمنع تخزينها في CDN ويهدر استعلامات الجلسة.
 */
Route::get(config('seo.sitemap.route', '/sitemap.xml'), [SitemapController::class, 'index'])
    ->name('seo.sitemap');

Route::get('/sitemap-{group}.xml', [SitemapController::class, 'group'])
    ->where('group', '[a-z0-9\-]+')
    ->name('seo.sitemap.group');

Route::get('/robots.txt', [SitemapController::class, 'robots'])
    ->name('seo.robots');
