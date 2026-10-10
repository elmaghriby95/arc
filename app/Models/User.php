<?php

namespace App\Models;

use Database\Factories\UserFactory;
use App\Enums\Permission;
use App\Services\LendingScopeService;
use App\Services\TransactionScopeService;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'employee_number', 'password', 'must_change_password', 'password_changed_at', 'role_id', 'department_id', 'view_descendant_units', 'language_id', 'avatar_path', 'last_login_at', 'last_login_ip'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** Days before a self-chosen password must be replaced. */
    public const PASSWORD_MAX_AGE_DAYS = 30;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'view_descendant_units' => 'boolean',
            'granted_permissions' => 'array',
            'revoked_permissions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (): bool {
            return false;
        });
    }

    public function needsPasswordChange(): bool
    {
        if (! array_key_exists('must_change_password', $this->attributes)) {
            return false;
        }

        return $this->must_change_password
            || $this->password_changed_at === null
            || $this->passwordHasExpired();
    }

    public function passwordHasExpired(): bool
    {
        if ($this->password_changed_at === null) {
            return false;
        }

        return $this->password_changed_at->copy()
            ->addDays(self::PASSWORD_MAX_AGE_DAYS)
            ->lte(now());
    }

    public function passwordChangeReason(): ?string
    {
        if (! $this->needsPasswordChange()) {
            return null;
        }

        if ($this->must_change_password || $this->password_changed_at === null) {
            return 'initial';
        }

        return 'expired';
    }

    public function replacePassword(string $password): void
    {
        $this->password = $password;
        $this->must_change_password = false;
        $this->password_changed_at = now();
    }

    public function assignTemporaryPassword(string $password): void
    {
        $this->password = $password;
        $this->must_change_password = true;
        $this->password_changed_at = null;
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function avatarInitial(): string
    {
        return mb_substr($this->name, 0, 1);
    }

    public function roleSlug(): string
    {
        return $this->role?->slug ?? 'user';
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function hasPermission(string $permission): bool
    {
        if (in_array($permission, $this->revoked_permissions ?? [], true)) {
            return false;
        }

        if (in_array($permission, $this->granted_permissions ?? [], true)) {
            return true;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    public function permissionsCanBeCustomizedBy(User $actor): bool
    {
        return ! $this->isAdmin() || $actor->isAdmin();
    }

    /** @return list<string> */
    public function effectivePermissionValues(): array
    {
        $rolePermissions = self::permissionValuesForRole($this->role);

        return array_values(array_unique(array_merge(
            array_values(array_diff($rolePermissions, $this->revoked_permissions ?? [])),
            $this->granted_permissions ?? [],
        )));
    }

    /** @param  list<string>  $desired */
    public function syncPermissionOverrides(Role $role, array $desired): void
    {
        $allowed = $role->isSuperAdmin()
            ? PermissionRegistry::allValues()
            : PermissionRegistry::assignableValues();

        $desired = array_values(array_unique(array_intersect($desired, $allowed)));
        $rolePermissions = self::permissionValuesForRole($role);

        $granted = array_values(array_diff($desired, $rolePermissions));
        $revoked = array_values(array_diff($rolePermissions, $desired));
        sort($granted);
        sort($revoked);

        $this->granted_permissions = $granted === [] ? null : $granted;
        $this->revoked_permissions = $revoked === [] ? null : $revoked;
    }

    /** @return list<string> */
    public static function permissionValuesForRole(?Role $role): array
    {
        if (! $role) {
            return [];
        }

        if ($role->isSuperAdmin()) {
            return PermissionRegistry::allValues();
        }

        return array_values(array_unique($role->permissions ?? []));
    }

    public function canAccessReports(): bool
    {
        if ($this->hasPermission(\App\Enums\Permission::ReportsView->value)) {
            return true;
        }

        foreach (\App\Enums\ReportType::cases() as $type) {
            if ($this->hasPermission($type->permission())) {
                return true;
            }
        }

        return false;
    }

    public function canViewReport(\App\Enums\ReportType $type): bool
    {
        if ($type->isAdminOnly() && ! $this->isAdmin()) {
            return false;
        }

        if ($this->hasPermission(\App\Enums\Permission::ReportsView->value)) {
            return true;
        }

        return $this->hasPermission($type->permission());
    }

    public function homeUrl(): string
    {
        $routes = [
            'dashboard.view' => 'dashboard',
            'transactions.view' => 'transactions.index',
            'documents.view' => 'documents.index',
            'departments.view' => 'departments.index',
            'settings.view' => 'settings.index',
            'profile.view' => 'profile.edit',
        ];

        foreach ($routes as $permission => $route) {
            if ($this->hasPermission($permission)) {
                return route($route, absolute: false);
            }
        }

        if ($this->canAccessReports()) {
            return route('reports.index', absolute: false);
        }

        abort(403, __('messages.no_access'));
    }

    public function isAdmin(): bool
    {
        return $this->role?->isSuperAdmin() ?? false;
    }

    public function orgBreadcrumb(): ?string
    {
        return $this->department?->breadcrumb();
    }

    /**
     * Units this user is assigned to see.
     * The descendant option includes child administrations and departments.
     *
     * @return list<int>
     */
    public function assignedOrgUnitIds(): array
    {
        if (! $this->department_id) {
            return [];
        }

        if ($this->view_descendant_units) {
            return Department::descendantIdsIncludingSelf((int) $this->department_id);
        }

        return [(int) $this->department_id];
    }

    /** @return list<int>|null null = unrestricted (admin) */
    public function orgScopeDepartmentIds(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        return $this->assignedOrgUnitIds();
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

        $departmentId = (int) $departmentId;
        $scope = array_map(intval(...), $scope);

        return in_array($departmentId, $scope, true);
    }

    public function canAccessDocument(Document $document): bool
    {
        return $this->canAccessDepartment($document->department_id);
    }

    public function canAccessAttachment(TransactionAttachment $attachment): bool
    {
        $attachment->loadMissing('transaction');

        if ($this->canAccessTransaction($attachment->transaction)) {
            return true;
        }

        return app(LendingScopeService::class)
            ->canAccessTransactionAttachmentsForLending($this, $attachment->transaction);
    }

    public function canAccessFolder(Folder $folder): bool
    {
        return $this->canAccessDepartmentInFolderScope($folder->department_id);
    }

    public function canAccessTransaction(Transaction $transaction): bool
    {
        return app(TransactionScopeService::class)->canViewTransaction($this, $transaction);
    }

    /** @return list<int>|null null = unrestricted (transactions.view-all only) */
    public function transactionOrgScopeDepartmentIds(): ?array
    {
        if ($this->hasPermission(Permission::TransactionsViewAll->value)) {
            return null;
        }

        return $this->orgScopeDepartmentIds();
    }

    /**
     * Org scope for reports. null = all organizational units.
     *
     * @return list<int>|null
     */
    public function reportOrgScopeDepartmentIds(): ?array
    {
        if ($this->hasUnrestrictedReportAccess()) {
            return null;
        }

        return $this->orgScopeDepartmentIds();
    }

    public function hasUnrestrictedReportAccess(): bool
    {
        return $this->isAdmin()
            || $this->hasPermission(Permission::ReportsViewAll->value);
    }

    /**
     * Org scope for folder trees/pickers. null = all folders.
     *
     * @return list<int>|null
     */
    public function folderOrgScopeDepartmentIds(): ?array
    {
        if ($this->hasUnrestrictedFolderAccess()) {
            return null;
        }

        return $this->orgScopeDepartmentIds();
    }

    public function hasUnrestrictedFolderAccess(): bool
    {
        return $this->isAdmin()
            || $this->hasPermission(Permission::SettingsFoldersViewAll->value);
    }

    public function canAccessDepartmentInFolderScope(?int $departmentId): bool
    {
        $scope = $this->folderOrgScopeDepartmentIds();

        if ($scope === null) {
            return true;
        }

        if ($departmentId === null) {
            return false;
        }

        $departmentId = (int) $departmentId;
        $scope = array_map(intval(...), $scope);

        return in_array($departmentId, $scope, true);
    }

    public function canAccessDepartmentInReportScope(?int $departmentId): bool
    {
        $scope = $this->reportOrgScopeDepartmentIds();

        if ($scope === null) {
            return true;
        }

        if ($departmentId === null) {
            return false;
        }

        $departmentId = (int) $departmentId;
        $scope = array_map(intval(...), $scope);

        return in_array($departmentId, $scope, true);
    }
}
