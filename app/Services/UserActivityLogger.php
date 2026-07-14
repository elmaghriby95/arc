<?php

namespace App\Services;

use App\Models\AuditLog;
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
