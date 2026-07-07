<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var list<string> */
    private array $newPermissions = [
        'settings.qr-code.view',
        'settings.qr-code.edit',
    ];

    public function up(): void
    {
        $admin = Role::where('slug', 'admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = array_values(array_unique(array_merge($admin->permissions ?? [], $this->newPermissions)));
        $admin->update(['permissions' => $permissions]);
    }

    public function down(): void
    {
        $admin = Role::where('slug', 'admin')->first();

        if (! $admin) {
            return;
        }

        $permissions = array_values(array_filter(
            $admin->permissions ?? [],
            fn (string $permission) => ! in_array($permission, $this->newPermissions, true)
        ));

        $admin->update(['permissions' => $permissions]);
    }
};
