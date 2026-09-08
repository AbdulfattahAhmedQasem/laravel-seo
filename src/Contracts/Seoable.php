<?php

namespace IProfile\Seo\Contracts;

/**
 * عقد صريح بدل method_exists المتناثر.
 *
 * الفائدة العملية: PHPStan والمحرر يريان الخطأ قبل التشغيل، وموديل
 * نُسي فيه seoUrl() يُكتشف عند التطوير لا عند اكتشاف أن خريطة الموقع
 * تحتوي 50 ألف رابط متطابق.
 */
interface Seoable
{
    /**
     * الرابط العام لهذه الصفحة — مطلق، بلا معاملات استعلام.
     */
    public function seoUrl(): string;
}
