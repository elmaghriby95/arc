<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\NotCurrentPassword;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->needsPasswordChange()) {
            return redirect()->to($user->homeUrl());
        }

        return view('auth.change-password', [
            'reason' => $user->passwordChangeReason(),
        ]);
    }

    public function store(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), new NotCurrentPassword($user)],
        ]);

        $user->replacePassword($validated['password']);
        $user->save();

        $logger->logPasswordChange($user, $request);

        return redirect()
            ->intended($user->homeUrl())
            ->with('success', __('auth.password_changed'));
    }
}
