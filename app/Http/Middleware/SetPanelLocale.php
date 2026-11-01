<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The staff panel is in English until NBI decides (clarification question 14).
 * APP_LOCALE stays Nepali, because it is the public pages' default.
 */
class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('en');

        return $next($request);
    }
}
