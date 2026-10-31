<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages take their language from the URL, /ne/... or /en/... (P2). The
 * last language used is kept in a cookie, so "/" can send a returning visitor to it.
 */
class SetLocale
{
    public const LOCALES = ['ne', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        if ($request->cookie('locale') !== $locale) {
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        }

        return $next($request);
    }
}
