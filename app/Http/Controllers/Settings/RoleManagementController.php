<?php

namespace App\Http\Controllers\Settings;

use App\Support\PermissionRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get();

        return view('settings.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('settings.roles.create', [
            'permissionGroups' => PermissionRegistry::grouped(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $slug = $this->generateUniqueSlug($request->string('name'));

        Role::create([
            'name' => $request->string('name'),
            'slug' => $slug,
            'description' => $request->string('description'),
            'permissions' => Role::normalizePermissions($request->input('permissions', [])),
            'is_system' => false,
        ]);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', __('messages.role.created'));
    }

    public function edit(Role $role): View
    {
        return view('settings.roles.edit', [
            'role' => $role,
            'permissionGroups' => PermissionRegistry::grouped(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->string('name'),
            'description' => $request->string('description'),
            'permissions' => Role::normalizePermissions($request->input('permissions', [])),
        ]);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', __('messages.role.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->with('error', __('messages.role.cannot_delete_system'));
        }

        if ($role->users()->exists()) {
            return back()->with('error', __('messages.role.cannot_delete_in_use'));
        }

        $role->delete();

        return redirect()
            ->route('settings.roles.index')
            ->with('success', __('messages.role.deleted'));
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : 'role';
        $counter = 1;

        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
