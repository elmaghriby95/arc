<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, UserActivityLogger $logger): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $logger->logLogin($user, $request);

        return redirect()->intended($user->homeUrl());
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $wasIdle = $request->boolean('idle');

        if ($user = $request->user()) {
            $logger->logLogout($user, $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $redirect = redirect()->route('login');

        if ($wasIdle) {
            return $redirect->with('status', __('auth.idle_logged_out'));
        }

        return $redirect;
    }
}
