<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UserActivityLogger
{
    public function log(
        User $user,
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function logLogin(User $user, ?Request $request = null): AuditLog
    {
        return $this->log($user, 'login', $user, null, null, $request);
    }

    public function logLogout(User $user, ?Request $request = null): AuditLog
    {
        return $this->log($user, 'logout', $user, null, null, $request);
    }

    public function logProfileUpdate(User $user, array $oldValues, array $newValues, ?Request $request = null): AuditLog
    {
        return $this->log($user, 'profile.updated', $user, $oldValues, $newValues, $request);
    }

    public function logAvatarUpdate(User $user, ?string $oldPath, ?string $newPath, ?Request $request = null): AuditLog
    {
        return $this->log(
            $user,
            'profile.avatar_updated',
            $user,
            ['avatar_path' => $oldPath],
            ['avatar_path' => $newPath],
            $request,
        );
    }

    public function logPasswordChange(User $user, ?Request $request = null): AuditLog
    {
        return $this->log($user, 'password.changed', $user, null, null, $request);
    }

    public function logAdminUserUpdate(
        User $admin,
        User $target,
        array $oldValues,
        array $newValues,
        ?Request $request = null,
    ): AuditLog {
        return $this->log($admin, 'admin.user.updated', $target, $oldValues, $newValues, $request);
    }

    public function logAdminAvatarUpdate(
        User $admin,
        User $target,
        ?string $oldPath,
        ?string $newPath,
        ?Request $request = null,
    ): AuditLog {
        return $this->log(
            $admin,
            'admin.user.avatar_updated',
            $target,
            ['avatar_path' => $oldPath],
            ['avatar_path' => $newPath],
            $request,
        );
    }

    public function logUserCreated(User $admin, User $created, ?Request $request = null): AuditLog
    {
        return $this->log($admin, 'admin.user.created', $created, null, [
            'name' => $created->name,
            'email' => $created->email,
            'role_id' => $created->role_id,
            'department_id' => $created->department_id,
            'view_descendant_units' => $created->view_descendant_units,
        ], $request);
    }

    public function logUserDeleted(User $admin, User $deleted, ?Request $request = null): AuditLog
    {
        return $this->log($admin, 'admin.user.deleted', $deleted, [
            'name' => $deleted->name,
            'email' => $deleted->email,
            'role_id' => $deleted->role_id,
            'department_id' => $deleted->department_id,
        ], null, $request);
    }

    public function logFolderCreated(User $actor, Model $folder, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'folder.created', $folder, null, $this->folderSnapshot($folder), $request);
    }

    public function logFolderUpdated(User $actor, Model $folder, array $oldValues, array $newValues, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'folder.updated', $folder, $oldValues, $newValues, $request);
    }

    public function logFolderDeleted(User $actor, Model $folder, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'folder.deleted', $folder, $this->folderSnapshot($folder), null, $request);
    }

    public function logDepartmentCreated(User $actor, Model $department, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'department.created', $department, null, $this->departmentSnapshot($department), $request);
    }

    public function logDepartmentUpdated(User $actor, Model $department, array $oldValues, array $newValues, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'department.updated', $department, $oldValues, $newValues, $request);
    }

    public function logDepartmentDeleted(User $actor, Model $department, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'department.deleted', $department, $this->departmentSnapshot($department), null, $request);
    }

    public function logRoleCreated(User $actor, Role $role, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'role.created', $role, null, $this->roleIdentity($role), $request);
    }

    /** @param  array{name?: string|null, description?: string|null, permissions?: list<string>}  $before */
    public function logRoleUpdated(User $actor, Role $role, array $before, ?Request $request = null): ?AuditLog
    {
        $old = [
            'name' => $before['name'] ?? null,
            'description' => $before['description'] ?? null,
        ];
        $new = $this->roleIdentity($role);

        $oldPermissions = Role::normalizePermissions($before['permissions'] ?? []);
        $newPermissions = Role::normalizePermissions($role->permissions ?? []);
        $added = array_values(array_diff($newPermissions, $oldPermissions));
        $removed = array_values(array_diff($oldPermissions, $newPermissions));

        if ($added !== []) {
            $old['permissions_added'] = null;
            $new['permissions_added'] = $this->permissionLabels($added);
        }

        if ($removed !== []) {
            $old['permissions_removed'] = null;
            $new['permissions_removed'] = $this->permissionLabels($removed);
        }

        return $this->logModelChange($actor, 'role.updated', $role, $old, $new, $request);
    }

    public function logRoleDeleted(User $actor, Role $role, ?Request $request = null): AuditLog
    {
        return $this->log($actor, 'role.deleted', $role, $this->roleIdentity($role), null, $request);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function logModelChange(
        User $actor,
        string $action,
        Model $model,
        array $oldValues,
        array $newValues,
        ?Request $request = null,
    ): ?AuditLog {
        [$old, $new] = $this->onlyChanged($oldValues, $newValues);

        if ($old === [] && $new === []) {
            return null;
        }

        return $this->log($actor, $action, $model, $old, $new, $request);
    }

    /** @return array<string, mixed> */
    private function folderSnapshot(Model $folder): array
    {
        return [
            'name' => $folder->getAttribute('name'),
            'department_id' => $folder->getAttribute('department_id'),
            'parent_id' => $folder->getAttribute('parent_id'),
            'cabinet_number' => $folder->getAttribute('cabinet_number'),
            'row_number' => $folder->getAttribute('row_number'),
            'box_number' => $folder->getAttribute('box_number'),
        ];
    }

    /** @return array{name: mixed, description: mixed} */
    private function roleIdentity(Role $role): array
    {
        return [
            'name' => $role->name,
            'description' => $role->description,
        ];
    }

    /** @param  list<string>  $permissions */
    private function permissionLabels(array $permissions): string
    {
        $labels = collect($permissions)
            ->map(fn (string $permission) => Permission::tryFrom($permission)?->label() ?? $permission)
            ->values();

        $shown = $labels->take(8)->implode('، ');
        $extra = $labels->count() - 8;

        if ($extra > 0) {
            $shown .= ' +'.$extra;
        }

        return $shown;
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function onlyChanged(array $oldValues, array $newValues): array
    {
        $old = [];
        $new = [];

        foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $key) {
            $from = $oldValues[$key] ?? null;
            $to = $newValues[$key] ?? null;

            if ($this->sameValue((string) $key, $from, $to)) {
                continue;
            }

            $old[$key] = $from;
            $new[$key] = $to;
        }

        return [$old, $new];
    }

    private function sameValue(string $key, mixed $from, mixed $to): bool
    {
        if (is_bool($from) || is_bool($to) || str_starts_with($key, 'is_')) {
            return (bool) $from === (bool) $to;
        }

        if (is_array($from) || is_array($to)) {
            return json_encode($from) === json_encode($to);
        }

        return trim((string) ($from ?? '')) === trim((string) ($to ?? ''));
    }

    /** @return array<string, mixed> */
    private function departmentSnapshot(Model $department): array
    {
        return [
            'name' => $department->getAttribute('name'),
            'code' => $department->getAttribute('code'),
            'unit_label' => $department->getAttribute('unit_label'),
            'parent_id' => $department->getAttribute('parent_id'),
        ];
    }
}
