<?php

namespace App\Models;

use App\Enums\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
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
            $role->permissions = self::normalizePermissions($role->permissions);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
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
        return array_values(array_unique(array_merge(
            self::baselinePermissions(),
            $permissions ?? [],
        )));
    }
}
