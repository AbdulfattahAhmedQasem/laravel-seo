<?php

namespace IProfile\Seo\Support;

/**
 * ترميز JSON-LD آمن للطباعة داخل <script>.
 *
 * السياق: محتوى <script> في HTML لا يمر عبر مفكّك كيانات HTML، فلا ينفع
 * الهروب بـ htmlspecialchars. المتصفح ينهي البلوك عند أول "</script>"
 * حرفياً — حتى داخل سلسلة JSON. لذلك يجب ترميز "<" و ">" كوحدات \u.
 *
 * JSON_HEX_TAG يحوّل < > إلى < >
 * JSON_HEX_AMP يحوّل & إلى & (يمنع كسر السياقات المرمّزة بكيانات)
 * JSON_HEX_APOS و JSON_HEX_QUOT يؤمّنان الطباعة داخل خاصية HTML عند الحاجة
 *
 * JSON_UNESCAPED_UNICODE محفوظ عمداً: النص العربي يبقى مقروءاً لجوجل
 * وللمطوّر، وهو آمن لأن خطر الحقن محصور في محارف ASCII المرمّزة أعلاه.
 */
final class Json
{
    public const SCRIPT_FLAGS = JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;

    /**
     * @param  array<string,mixed>  $data
     */
    public static function forScript(array $data): string
    {
        $encoded = json_encode(self::prune($data), self::SCRIPT_FLAGS);

        // json_encode يفشل مع UTF-8 غير صالح أو INF/NAN. لا نطبع "false".
        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * يزيل القيم الفارغة تكرارياً — schema.org يرفض القيم null،
     * والمفاتيح الفارغة تُضعف جودة البيانات المنظمة في عين جوجل.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function prune(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::prune($value);
                $data[$key] = $value;
            }

            // القيم الرقمية 0 و "0" مشروعة (مثل priority) فلا تُحذف بـ empty().
            $isEmpty = $value === null
                || $value === ''
                || $value === []
                || (is_array($value) && array_keys($value) === ['@type']);

            if ($isEmpty) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
