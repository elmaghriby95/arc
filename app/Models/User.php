<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'department_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role?->hasPermission($permission) ?? false;
    }

    public function homeUrl(): string
    {
        $routes = [
            'dashboard.view' => 'dashboard',
            'transactions.view' => 'transactions.index',
            'documents.view' => 'documents.index',
            'departments.view' => 'departments.index',
            'categories.view' => 'categories.index',
            'settings.view' => 'settings.index',
            'profile.view' => 'profile.edit',
        ];

        foreach ($routes as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return route($route, absolute: false);
            }
        }

        abort(403, 'لا تملك صلاحية الوصول إلى أي صفحة في النظام. تواصل مع مدير النظام.');
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === 'admin';
    }

    public function orgBreadcrumb(): ?string
    {
        return $this->department?->breadcrumb();
    }

    /** @return list<int>|null null = unrestricted (admin) */
    public function orgScopeDepartmentIds(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        if (! $this->department_id) {
            return [];
        }

        return Department::descendantIdsIncludingSelf($this->department_id);
    }

    public function appliesOrgScope(): bool
    {
        return $this->orgScopeDepartmentIds() !== null;
    }

    public function canAccessDepartment(?int $departmentId): bool
    {
        $scope = $this->orgScopeDepartmentIds();

        if ($scope === null) {
            return true;
        }

        if ($departmentId === null) {
            return false;
        }

        return in_array($departmentId, $scope, true);
    }

    public function canAccessDocument(Document $document): bool
    {
        return $this->canAccessDepartment($document->department_id);
    }

    public function canAccessAttachment(TransactionAttachment $attachment): bool
    {
        $attachment->loadMissing('transaction');

        return $this->canAccessTransaction($attachment->transaction);
    }

    public function canAccessFolder(Folder $folder): bool
    {
        return $this->canAccessDepartment($folder->department_id);
    }

    public function canAccessTransaction(Transaction $transaction): bool
    {
        return $this->canAccessDepartment($transaction->department_id);
    }
}
