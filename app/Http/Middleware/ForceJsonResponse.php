<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces every API request to be treated as JSON, even when the client forgets the
 * `Accept: application/json` header. This makes Laravel's built-in handling — the
 * `Authenticate` middleware redirect, validation responses, abort() pages — choose
 * the JSON branch (401/422 payloads) instead of redirecting to the web login page.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
