<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->two_factor_enabled) {
            return $next($request);
        }

        if (session(config('google2fa.session_var'))) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.challenge', 'two-factor.verify', 'logout')) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge');
    }
}