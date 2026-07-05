<?php

use App\Enums\Permission;
use App\Enums\ReportType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @return list<string> */
    private function permissions(): array
    {
        return array_merge(
            [Permission::ReportsView->value],
            ReportType::permissionValues(),
        );
    }

    public function up(): void
    {
        $permissions = $this->permissions();

        foreach (['admin', 'manager'] as $slug) {
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
        $remove = $this->permissions();

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
