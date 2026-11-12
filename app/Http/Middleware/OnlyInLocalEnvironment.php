<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Development aids (the style overview page) exist only in the local
 * environment. Everywhere else the route answers 404, as if it were not there.
 * The route stays registered, so Wayfinder generates the same routes in every
 * environment, CI included.
 */
class OnlyInLocalEnvironment
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local'), 404);

        return $next($request);
    }
}
