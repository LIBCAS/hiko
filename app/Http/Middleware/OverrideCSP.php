<?php

namespace App\Http\Middleware;

use Closure;

class OverrideCSP
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' lib.cas.cz libj.cas.cz inqool.cz;");
        return $response;
    }
}
