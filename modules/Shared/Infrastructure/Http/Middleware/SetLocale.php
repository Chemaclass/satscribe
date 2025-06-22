<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Faq\Domain\Repository\FaqRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

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

    private function resolveLocale(Request $request): string
    {
        if ($request->has('lang')) {
            session(['app_locale' => $request->get('lang')]);
        }

        return session('app_locale', config('app.locale'));
    }
}
