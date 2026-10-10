<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\NotCurrentPassword;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', new NotCurrentPassword($user)],
        ]);

        $user->replacePassword($validated['password']);
        $user->save();

        $logger->logPasswordChange($user, $request);

        return back()->with('status', 'password-updated');
    }
}
