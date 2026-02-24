<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->input('lang') ?? $request->header('X-Language');

        if (!$locale && $request->hasSession() && $request->session()->has('locale')) {
            $locale = $request->session()->get('locale');
        }

        if ($locale && in_array($locale, ['cs', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
