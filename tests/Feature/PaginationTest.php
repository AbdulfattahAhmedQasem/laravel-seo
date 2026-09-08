<?php

use IProfile\Seo\Facades\Seo;

it('يبقي canonical ذاتياً على الصفحات المرقّمة', function () {
    // جوجل ألغى rel=prev/next؛ canonical يشير للصفحة الأولى يُخفي
    // المحتوى العميق عن الفهرسة تماماً.
    $this->get('/');

    $url = Seo::title('المدونة')->data()->canonical(url('/blog').'?page=3')->resolvedCanonical();

    expect($url)->toContain('page=3');
});

it('يمنع فهرسة صفحة مرقّمة خارج النطاق', function () {
    Seo::title('المدونة')->paginated(currentPage: 9, lastPage: 3);

    expect(Seo::data()->resolvedRobots())->toContain('noindex');
});

it('لا يمنع فهرسة صفحة مرقّمة صالحة', function () {
    Seo::title('المدونة')->paginated(currentPage: 2, lastPage: 3);

    expect(Seo::data()->resolvedRobots())->not->toContain('noindex');
});
