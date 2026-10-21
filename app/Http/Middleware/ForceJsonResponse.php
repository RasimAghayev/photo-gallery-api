<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers of the API are always JSON.
 *
 * The application has no page, so a request that expects a page cannot be
 * answered with one. The header of the answer is set here, so the framework
 * answers an unauthenticated request with the status 401 and a JSON body
 * instead of a redirect to a route that does not exist.
 */
class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
