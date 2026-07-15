<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\UserActivityFeed;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request, UserActivityFeed $activityFeed): View
    {
        $user = $request->user()->load(['role', 'department', 'language']);

        return view('profile.edit', [
            'user' => $user,
            'stats' => $activityFeed->statsFor($user),
            'activities' => $activityFeed->forUser($user),
            'orgBreadcrumb' => $user->orgBreadcrumb(),
        ]);
    }

    public function update(ProfileUpdateRequest $request, UserActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $oldValues = $user->only(['name', 'email']);
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->wasChanged(['name', 'email'])) {
            $logger->logProfileUpdate(
                $user,
                $oldValues,
                $user->only(['name', 'email']),
                $request,
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateAvatar(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => [
                'required',
                'image',
                'max:2048',
                File::types(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $validated['avatar']->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_path' => $path]);

        $logger->logAvatarUpdate($user, $oldPath, $path, $request);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    public function destroyAvatar(Request $request, UserActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->avatar_path;

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
            $user->update(['avatar_path' => null]);
            $logger->logAvatarUpdate($user, $oldPath, null, $request);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->isAdmin()) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => __('messages.user.cannot_delete_admin')], 'userDeletion');
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
