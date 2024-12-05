<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $locale = $request->session()->get('locale');

            if ($locale && in_array($locale, ['cs', 'en'])) {
                App::setLocale($locale);
            } else {
                App::setLocale(config('app.locale')); // Set to default locale
            }
        } catch (\Exception $e) {
            Log::error('Error setting locale.', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'current_locale' => $locale,
            ]);
            // Optionally, you can abort or handle the error as needed
            abort(400, 'Invalid locale setting.');
        }

        return $next($request);
    }
}
