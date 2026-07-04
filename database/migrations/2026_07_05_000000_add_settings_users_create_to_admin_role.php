<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $admin = Role::where('slug', 'admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = $admin->permissions ?? [];

        if (! in_array('settings.users.create', $permissions, true)) {
            $permissions[] = 'settings.users.create';
            $admin->update(['permissions' => $permissions]);
        }
    }

    public function down(): void
    {
        $admin = Role::where('slug', 'admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = array_values(array_filter(
            $admin->permissions ?? [],
            fn (string $permission) => $permission !== 'settings.users.create'
        ));

        $admin->update(['permissions' => $permissions]);
    }
};
