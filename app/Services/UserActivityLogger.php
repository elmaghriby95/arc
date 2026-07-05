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
}
