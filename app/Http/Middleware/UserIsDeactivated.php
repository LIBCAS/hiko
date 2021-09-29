<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserIsDeactivated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->isDeactivated()) {
            auth()->logout();
            return redirect()->route('login')->withErrors('Váš účet byl deaktivován.');
        }

        return $next($request);
    }
}
