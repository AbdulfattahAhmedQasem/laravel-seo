<?php

namespace IProfile\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use IProfile\Seo\Models\SeoRedirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * يعترض استجابات 404 فقط — لا يضيف أي استعلام على الطلبات الناجحة.
 */
class HandleSeoRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('seo.redirects.enabled', true) || $response->getStatusCode() !== 404) {
            return $response;
        }

        $path = SeoRedirect::normalize($request->path());
        $map = SeoRedirect::map();

        if (! isset($map[$path])) {
            return $response;
        }

        if (config('seo.redirects.track_hits', true)) {
            SeoRedirect::where('from', $path)->update([
                'hits' => DB::raw('hits + 1'),
                'last_hit_at' => now(),
            ]);
        }

        $target = $map[$path]['to'];
        $query = $request->getQueryString();

        return redirect(
            $query ? $target.'?'.$query : $target,
            $map[$path]['status'] ?: 301
        );
    }
}
