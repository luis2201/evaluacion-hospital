<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; img-src 'self' data:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'");

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->routeIs('login', 'password.*', 'evaluations.results*', 'audit.*', 'notifications.*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
