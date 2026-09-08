<?php

namespace IProfile\Seo\Support;

/**
 * تحليل بسيط شبيه بـ Yoast — يُستخدم في لوحة التحكم لإرشاد كاتب المحتوى.
 */
class SeoScore
{
    /**
     * @return array{score:int, checks:array<int,array{label:string, passed:bool, hint:string}>}
     */
    public static function analyze(
        ?string $title,
        ?string $description,
        ?string $keyword = null,
        ?string $body = null,
        ?string $slug = null,
    ): array {
        $limits = config('seo.limits');
        $titleLen = mb_strlen($title ?? '');
        $descLen = mb_strlen($description ?? '');
        $keyword = $keyword ? mb_strtolower(trim($keyword)) : null;

        $checks = [
            [
                'label'  => 'طول العنوان',
                'passed' => $titleLen >= $limits['title']['min'] && $titleLen <= $limits['title']['max'],
                'hint'   => "{$titleLen} حرفاً — المثالي {$limits['title']['min']}-{$limits['title']['max']}",
            ],
            [
                'label'  => 'طول الوصف',
                'passed' => $descLen >= $limits['description']['min'] && $descLen <= $limits['description']['max'],
                'hint'   => "{$descLen} حرفاً — المثالي {$limits['description']['min']}-{$limits['description']['max']}",
            ],
            [
                'label'  => 'الكلمة المفتاحية في العنوان',
                'passed' => $keyword && str_contains(mb_strtolower($title ?? ''), $keyword),
                'hint'   => 'ضع الكلمة المفتاحية قرب بداية العنوان',
            ],
            [
                'label'  => 'الكلمة المفتاحية في الوصف',
                'passed' => $keyword && str_contains(mb_strtolower($description ?? ''), $keyword),
                'hint'   => 'اذكر الكلمة المفتاحية مرة واحدة في الوصف',
            ],
            [
                'label'  => 'الكلمة المفتاحية في الرابط',
                'passed' => $keyword && $slug && str_contains(mb_strtolower($slug), str_replace(' ', '-', $keyword)),
                'hint'   => 'اجعل الرابط يحتوي الكلمة المفتاحية',
            ],
            [
                'label'  => 'طول المحتوى',
                'passed' => $body && str_word_count(strip_tags($body)) >= 300,
                'hint'   => 'الحد الأدنى الموصى به 300 كلمة',
            ],
        ];

        $passed = count(array_filter($checks, fn ($c) => $c['passed']));

        return [
            'score'  => (int) round($passed / count($checks) * 100),
            'checks' => $checks,
        ];
    }
}
