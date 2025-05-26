<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
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

