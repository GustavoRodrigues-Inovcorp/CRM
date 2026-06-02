<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user?->two_factor_enabled && $user->google2fa_secret) {
            return redirect()->route('two-factor.challenge');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Display the two-factor challenge view.
     */
    public function twoFactorChallenge(Request $request): Response|RedirectResponse
    {
        if (!$request->user()?->two_factor_enabled || session(config('google2fa.session_var'))) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'status' => session('status'),
            'email' => $request->user()->email,
        ]);
    }

    /**
     * Verify the submitted two-factor code.
     */
    public function verifyTwoFactorChallenge(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user?->two_factor_enabled || !$user->google2fa_secret) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $google2fa = new Google2FA();
        $google2fa->setWindow((int) config('google2fa.window', 1));
        $valid = $google2fa->verifyKey($user->google2fa_secret, $validated['code']);

        if (!$valid) {
            return back()->withErrors([
                'code' => 'Código inválido. Tenta novamente.',
            ]);
        }

        session([config('google2fa.session_var') => true]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
