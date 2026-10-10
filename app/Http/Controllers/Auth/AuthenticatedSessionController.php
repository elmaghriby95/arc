<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Language;
use App\Models\User;
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
        ]);
        $this->persistChosenLocale($request, $user);
        $user->save();

        $logger->logLogin($user, $request);

        if ($user->needsPasswordChange()) {
            return redirect()->route('password.change');
        }

        return redirect()->intended($user->homeUrl());
    }

    private function persistChosenLocale(Request $request, User $user): void
    {
        $code = $request->session()->get('locale');

        if (! is_string($code) || $code === '') {
            if ($user->language_id) {
                $saved = Language::query()
                    ->whereKey($user->language_id)
                    ->where('is_active', true)
                    ->value('code');

                if (is_string($saved) && $saved !== '') {
                    $request->session()->put('locale', $saved);
                }
            }

            return;
        }

        $languageId = Language::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');

        if ($languageId) {
            $user->language_id = $languageId;
        }
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
