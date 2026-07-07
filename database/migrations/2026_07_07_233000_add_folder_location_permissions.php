<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @return array<string, list<string>> */
    private function rolePermissions(): array
    {
        return [
            'admin' => [
                Permission::SettingsFoldersLocationView->value,
                Permission::SettingsFoldersLocationEdit->value,
            ],
            'manager' => [
                Permission::SettingsFoldersLocationView->value,
                Permission::SettingsFoldersLocationEdit->value,
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->rolePermissions() as $slug => $permissions) {
            $role = Role::where('slug', $slug)->first();

            if (! $role) {
                continue;
            }

            $role->update([
                'permissions' => array_values(array_unique(array_merge($role->permissions ?? [], $permissions))),
            ]);
        }
    }

    public function down(): void
    {
        $remove = array_unique(array_merge(...array_values($this->rolePermissions())));

        foreach (Role::all() as $role) {
            $role->update([
                'permissions' => array_values(array_filter(
                    $role->permissions ?? [],
                    fn (string $permission) => ! in_array($permission, $remove, true)
                )),
            ]);
        }
    }
};
