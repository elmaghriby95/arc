<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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

    public function edit(User $user): View
    {
        return view('settings.users.edit', [
            'user' => $user->load(['department', 'role']),
            'roles' => Role::orderByDesc('is_system')->orderBy('name')->get(),
            'orgUnits' => Department::optionsForSelect(),
            'breadcrumbs' => Department::breadcrumbMap(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()
            ->route('settings.users.index')
            ->with('success', __('messages.user.updated'));
    }
}
