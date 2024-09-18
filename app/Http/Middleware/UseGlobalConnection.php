<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class UseGlobalConnection
{
    public function handle($request, Closure $next)
    {
        DB::setDefaultConnection('mysql');
        
        return $next($request);
    }
}
