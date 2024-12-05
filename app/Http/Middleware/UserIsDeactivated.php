<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->isDeactivated()) {
                Log::warning('Deactivated user attempted to access the application.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                Auth::logout();

                return redirect()->route('login')->withErrors([
                    'account_deactivated' => 'Váš účet byl deaktivován.',
                ]);
            }
        }

        return $next($request);
    }
}
