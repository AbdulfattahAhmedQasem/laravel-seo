<?php

namespace IProfile\Seo\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use IProfile\Seo\Models\SeoRedirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * يعترض استجابات 404 فقط — صفر استعلامات على الطلبات الناجحة.
 *
 * يجب تسجيله عالمياً ($middleware->append) لا في مجموعة web: الروابط
 * القديمة لا تطابق أي روط، ومجموعات الروطات لا تُنفَّذ إطلاقاً على
 * الطلبات غير المطابقة — فلن يرى الميدل وير الـ 404 الذي وُجد لأجله.
 */
class HandleSeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldAttempt($request, $response)) {
            return $response;
        }

        try {
            $redirect = SeoRedirect::lookup($request->path());
        } catch (QueryException $e) {
            // جدول غير مهاجَر أو قاعدة بيانات معطّلة لا يجوز أن تحوّل
            // صفحة 404 عادية إلى 500. نُسجّل ونُكمل.
            Log::warning('تعذّر قراءة جدول seo_redirects: '.$e->getMessage());

            return $response;
        }

        if ($redirect === null) {
            return $response;
        }

        // 410 Gone: إشارة صريحة لجوجل بأن المحتوى أُزيل نهائياً، فيُسقطه
        // من الفهرس أسرع بكثير من 404 المتكرر.
        if ($redirect['status'] === 410) {
            $this->trackHit($request->path());

            abort(410);
        }

        $target = $redirect['to'];

        if (SeoRedirect::normalize($target) === SeoRedirect::normalize($request->path())) {
            return $response;
        }

        $this->trackHit($request->path());

        $query = $request->getQueryString();

        return redirect(
            $query ? $target.(str_contains($target, '?') ? '&' : '?').$query : $target,
            $redirect['status'] ?: 301
        );
    }

    protected function shouldAttempt(Request $request, Response $response): bool
    {
        if (! config('seo.redirects.enabled', true)) {
            return false;
        }

        if ($response->getStatusCode() !== 404) {
            return false;
        }

        // التحويل 301 يحوّل الطلب إلى GET ويُسقط الجسم — لا يُطبَّق إلا
        // على الطرق الآمنة.
        if (! $request->isMethodSafe()) {
            return false;
        }

        return ! $this->isAssetRequest($request);
    }

    /**
     * صورة أو ملف CSS مفقود لا يحتاج استعلام قاعدة بيانات. موقع مقصوف
     * بطلبات أصول غير موجودة كان سينهار على هذا الاستعلام.
     */
    protected function isAssetRequest(Request $request): bool
    {
        $extension = strtolower(pathinfo($request->path(), PATHINFO_EXTENSION));

        if ($extension === '') {
            return false;
        }

        return in_array($extension, (array) config('seo.redirects.ignored_extensions', []), true);
    }

    protected function trackHit(string $path): void
    {
        if (! config('seo.redirects.track_hits', true)) {
            return;
        }

        // تحديث مباشر بلا تحميل الموديل ولا إطلاق أحداث — صفر إبطال للكاش.
        SeoRedirect::query()
            ->where('from', SeoRedirect::normalize($path))
            ->update([
                'hits' => DB::raw('hits + 1'),
                'last_hit_at' => now(),
            ]);
    }
}
