<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Department;
use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use App\Services\UserActivityFeed;
use App\Services\UserActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Department::breadcrumbMap();

        return view('settings.users.index', [
            'users' => User::with(['department', 'role'])->latest()->paginate(15),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    public function create(): View
    {
        return view('settings.users.create', [
            'roles' => Role::orderByDesc('is_system')->orderBy('name')->get(),
            'orgUnits' => Department::optionsForSelect(),
            'breadcrumbs' => Department::breadcrumbMap(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', __('messages.user.created'));
    }

    public function edit(User $user, UserActivityFeed $activityFeed): View
    {
        return view('settings.users.edit', [
            'user' => $user->load(['department', 'role', 'language']),
            'roles' => Role::orderByDesc('is_system')->orderBy('name')->get(),
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'orgUnits' => Department::optionsForSelect(),
            'breadcrumbs' => Department::breadcrumbMap(),
            'stats' => $activityFeed->statsFor($user),
            'activities' => $activityFeed->forUser($user),
            'orgBreadcrumb' => $user->orgBreadcrumb(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserActivityLogger $logger): RedirectResponse
    {
        $oldValues = $user->only(['name', 'email', 'role_id', 'department_id', 'language_id']);
        $validated = $request->validated();

        $passwordChanged = filled($validated['password'] ?? null);
        unset($validated['password'], $validated['password_confirmation']);

        if ($validated['email'] !== $user->email) {
            $user->email_verified_at = now();
        }

        $user->fill($validated);

        if ($passwordChanged) {
            $user->password = $request->input('password');
        }

        $user->save();

        $logger->logAdminUserUpdate(
            $request->user(),
            $user,
            $oldValues,
            $user->only(['name', 'email', 'role_id', 'department_id', 'language_id']),
            $request,
        );

        if ($passwordChanged) {
            $logger->logPasswordChange($user, $request);
        }

        return redirect()
            ->route('settings.users.edit', $user)
            ->with('success', __('messages.user.updated'));
    }

    public function updateAvatar(Request $request, User $user, UserActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('settings.users.edit'), 403);

        $validated = $request->validate([
            'avatar' => [
                'required',
                'image',
                'max:2048',
                File::types(['jpg', 'jpeg', 'png', 'webp']),
            ],
        ]);

        $oldPath = $user->avatar_path;

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $validated['avatar']->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_path' => $path]);

        $logger->logAdminAvatarUpdate($request->user(), $user, $oldPath, $path, $request);

        return redirect()
            ->route('settings.users.edit', $user)
            ->with('success', __('messages.user.avatar_updated'));
    }

    public function destroyAvatar(Request $request, User $user, UserActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('settings.users.edit'), 403);

        $oldPath = $user->avatar_path;

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
            $user->update(['avatar_path' => null]);
            $logger->logAdminAvatarUpdate($request->user(), $user, $oldPath, null, $request);
        }

        return redirect()
            ->route('settings.users.edit', $user)
            ->with('success', __('messages.user.avatar_removed'));
    }
}
