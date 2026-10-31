<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTPS and security headers for every response (S1). Public pages get a
 * self-only policy; the admin panel gets the least Filament needs.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && ! app()->environment('local', 'testing')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $policy = $request->is('admin', 'admin/*')
            ? $this->adminPolicy(Vite::useCspNonce())
            : "default-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function adminPolicy(string $nonce): string
    {
        return "default-src 'self'; script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";
    }
}
