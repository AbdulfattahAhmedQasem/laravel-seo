<?php

namespace IProfile\Seo\Support;

use Illuminate\Support\Str;

/**
 * تحليل شبيه بـ Yoast — يُستخدم في لوحة التحكم لإرشاد كاتب المحتوى.
 *
 * ليس مقياس ترتيب: جوجل لا يقرأ هذه النتيجة. قيمته أنه يمنع الأخطاء
 * الشائعة (عنوان مقصوص، وصف مفقود) قبل النشر.
 */
final class SeoScore
{
    /**
     * @return array{score:int, checks:array<int,array{key:string, label:string, passed:bool, hint:string}>}
     */
    public static function analyze(
        ?string $title,
        ?string $description,
        ?string $keyword = null,
        ?string $body = null,
        ?string $slug = null,
    ): array {
        $limits = self::limits();
        $titleLen = mb_strlen(trim($title ?? ''));
        $descLen = mb_strlen(trim($description ?? ''));
        $keyword = $keyword ? Str::lower(trim($keyword)) : null;
        $words = self::countWords($body);

        $checks = [
            [
                'key' => 'title_length',
                'label' => 'طول العنوان',
                'passed' => $titleLen >= $limits['title']['min'] && $titleLen <= $limits['title']['max'],
                'hint' => "{$titleLen} حرفاً — المثالي {$limits['title']['min']}-{$limits['title']['max']}",
            ],
            [
                'key' => 'description_length',
                'label' => 'طول الوصف',
                'passed' => $descLen >= $limits['description']['min'] && $descLen <= $limits['description']['max'],
                'hint' => "{$descLen} حرفاً — المثالي {$limits['description']['min']}-{$limits['description']['max']}",
            ],
            [
                'key' => 'keyword_in_title',
                'label' => 'الكلمة المفتاحية في العنوان',
                'passed' => self::contains($title, $keyword),
                'hint' => 'ضع الكلمة المفتاحية قرب بداية العنوان',
            ],
            [
                'key' => 'keyword_in_description',
                'label' => 'الكلمة المفتاحية في الوصف',
                'passed' => self::contains($description, $keyword),
                'hint' => 'اذكر الكلمة المفتاحية مرة واحدة في الوصف',
            ],
            [
                'key' => 'keyword_in_slug',
                'label' => 'الكلمة المفتاحية في الرابط',
                'passed' => $keyword !== null && $slug !== null
                    && str_contains(Str::lower($slug), str_replace(' ', '-', $keyword)),
                'hint' => 'اجعل الرابط يحتوي الكلمة المفتاحية',
            ],
            [
                'key' => 'content_length',
                'label' => 'طول المحتوى',
                'passed' => $words >= 300,
                'hint' => "{$words} كلمة — الحد الأدنى الموصى به 300",
            ],
        ];

        $passed = count(array_filter($checks, fn ($check) => $check['passed']));

        return [
            'score' => (int) round($passed / count($checks) * 100),
            'checks' => $checks,
        ];
    }

    /**
     * عدّ كلمات يفهم UTF-8.
     *
     * str_word_count يعدّ البايتات لا الكلمات: 400 كلمة عربية تُحسب
     * 1800، فيمرّ فحص "300 كلمة" عند 66 كلمة فعلية. الفحص كان يكذب
     * على كاتب المحتوى في اللغة التي بُنيت الحزمة لها أصلاً.
     */
    public static function countWords(?string $body): int
    {
        if ($body === null || trim($body) === '') {
            return 0;
        }

        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '');

        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    protected static function contains(?string $haystack, ?string $needle): bool
    {
        return $haystack !== null && $needle !== null && $needle !== ''
            && str_contains(Str::lower($haystack), $needle);
    }

    /**
     * @return array{title:array{min:int,max:int}, description:array{min:int,max:int}}
     */
    protected static function limits(): array
    {
        $defaults = [
            'title' => ['min' => 30, 'max' => 60],
            'description' => ['min' => 120, 'max' => 160],
        ];

        $configured = (array) config('seo.limits', []);

        return [
            'title' => array_merge($defaults['title'], (array) ($configured['title'] ?? [])),
            'description' => array_merge($defaults['description'], (array) ($configured['description'] ?? [])),
        ];
    }
}
