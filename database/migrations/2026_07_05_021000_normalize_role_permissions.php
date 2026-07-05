<?php

use App\Models\Role;
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
    }

    public function down(): void
    {
        // Permission cleanup is not reverted.
    }
};
