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

        /*
         * Force browsers to remember that this ERP must only be accessed
         * through HTTPS.
         */
        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );

        /*
         * Prevent MIME-type sniffing.
         */
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        /*
         * Prevent the ERP from being embedded inside external frames.
         */
        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        /*
         * Limit referrer information sent to external websites.
         */
        $response->headers->set(
            'Referrer-Policy',
            'strict-origin-when-cross-origin'
        );

        /*
         * Disable browser capabilities that Octram ERP does not use.
         */
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        return $response;
    }
}
