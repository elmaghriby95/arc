<?php

namespace App\Services;

use App\Models\Role;
use App\Models\TransactionStatus;

class WorkflowPermissionService
{
    public function sync(TransactionStatus $status, ?string $previousPermissionKey = null): void
    {
        if ($status->is_initial) {
            $keyToRemove = $previousPermissionKey ?? $status->required_permission;

            if ($keyToRemove) {
                $this->stripPermissionFromAllRoles($keyToRemove);
            }

            if ($status->required_permission !== null) {
                $status->update(['required_permission' => null]);
            }

            return;
        }

        $newKey = $status->workflowPermissionKey();
        $oldKey = $previousPermissionKey ?? ($status->required_permission !== $newKey ? $status->required_permission : null);

        if ($oldKey && $oldKey !== $newKey) {
            $this->replacePermissionInRoles($oldKey, $newKey);
        }

        if ($status->required_permission !== $newKey) {
            $status->update(['required_permission' => $newKey]);
            $this->grantToAdmin($newKey);
        }
    }

    public function stripPermissionFromAllRoles(string $permissionKey): void
    {
        foreach (Role::all() as $role) {
            $permissions = $role->permissions ?? [];

            if (! in_array($permissionKey, $permissions, true)) {
                continue;
            }

            $role->update([
                'permissions' => Role::normalizePermissions(array_values(array_diff($permissions, [$permissionKey]))),
            ]);
        }
    }

    private function replacePermissionInRoles(string $oldKey, string $newKey): void
    {
        foreach (Role::all() as $role) {
            $permissions = $role->permissions ?? [];

            if (! in_array($oldKey, $permissions, true)) {
                continue;
            }

            $permissions = array_values(array_diff($permissions, [$oldKey]));

            if (! in_array($newKey, $permissions, true)) {
                $permissions[] = $newKey;
            }

            $role->update([
                'permissions' => Role::normalizePermissions($permissions),
            ]);
        }
    }

    private function grantToAdmin(string $permissionKey): void
    {
        $admin = Role::where('slug', 'admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = $admin->permissions ?? [];

        if (in_array($permissionKey, $permissions, true)) {
            return;
        }

        $permissions[] = $permissionKey;

        $admin->update([
            'permissions' => Role::normalizePermissions($permissions),
        ]);
    }
}
