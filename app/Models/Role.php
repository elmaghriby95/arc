<?php

namespace App\Models;

use App\Enums\Permission;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public const SUPER_ADMIN_SLUG = 'admin';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Role $role): void {
            if ($role->isSuperAdmin()) {
                $role->is_system = true;
                $role->permissions = self::superAdminPermissions();

                return;
            }

            $role->permissions = self::normalizePermissions($role->permissions);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return in_array($permission, PermissionRegistry::allValues(), true);
        }

        return in_array($permission, $this->permissions ?? [], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN_SLUG;
    }

    /** @return list<string> */
    public static function baselinePermissions(): array
    {
        return [
            Permission::DashboardView->value,
            Permission::ProfileView->value,
            Permission::ProfileEdit->value,
        ];
    }

    /** @param  list<string>|null  $permissions */
    public static function normalizePermissions(?array $permissions): array
    {
        $valid = PermissionRegistry::allValues();
        $filtered = array_values(array_intersect($permissions ?? [], $valid));

        return array_values(array_unique(array_merge(
            self::baselinePermissions(),
            $filtered,
        )));
    }

    /** @return list<string> */
    public static function superAdminPermissions(): array
    {
        return PermissionRegistry::allValues();
    }
}
