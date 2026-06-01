<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent browsers from caching dynamic HTML responses.
 *
 * Without these headers a browser may serve a stale (or broken)
 * page from its local cache — exactly the problem that caused the
 * auction detail page to appear blank after a deployment.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Only apply to HTML page responses, leave API / asset requests alone.
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') || empty($contentType)) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
