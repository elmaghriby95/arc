<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Role::all() as $role) {
            $role->update([
                'permissions' => Role::normalizePermissions($role->permissions),
            ]);
        }

        $defaultRoleId = Role::where('slug', 'user')->value('id');

        if ($defaultRoleId) {
            User::whereNull('role_id')->update(['role_id' => $defaultRoleId]);
        }
    }

    public function down(): void
    {
        // Baseline permissions are not reverted.
    }
};
