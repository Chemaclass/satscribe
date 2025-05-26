<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->get('lang');
        if ($locale) {
            session(['app_locale' => $locale]);
        }
        $locale = session('app_locale', config('app.locale'));
        app()->setLocale($locale);

        return $next($request);
    }
}
