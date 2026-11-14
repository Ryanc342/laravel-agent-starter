<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language of the request before the Inertia middleware shares the
 * page's text: the user's choice, the `locale` cookie, the browser's
 * Accept-Language, then English (Locale::resolve decides).
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Locale::resolve(
            $request->user()?->locale?->value,
            $request->cookie('locale'),
            array_values($request->getLanguages()),
        );

        app()->setLocale($locale->value);

        return $next($request);
    }
}
