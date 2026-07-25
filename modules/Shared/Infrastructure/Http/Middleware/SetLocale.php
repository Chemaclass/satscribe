<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

use function in_array;
use function is_array;
use function is_string;

final readonly class SetLocale
{
    public function __construct(
        private FaqRepositoryInterface $faqRepository,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        View::share('hasFaqs', $this->faqRepository->hasAny($locale));

        return $next($request);
    }

    /**
     * ?lang= is unvalidated user input that reaches app()->setLocale(), which
     * Laravel expands into a translation file path. Anything outside the
     * shipped set — a bogus code, a traversal attempt, a non-string left in the
     * session — resolves to the default instead.
     */
    private function resolveLocale(Request $request): string
    {
        $default = as_string(config('app.locale'), 'en');

        if ($request->has('lang')) {
            session(['app_locale' => $request->get('lang')]);
        }

        $locale = session('app_locale', $default);
        $supported = config('app.supported_locales');

        if (!is_string($locale) || !is_array($supported) || !in_array($locale, $supported, true)) {
            return $default;
        }

        return $locale;
    }
}
