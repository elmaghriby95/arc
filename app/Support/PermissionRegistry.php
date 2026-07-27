<?php

namespace App\Support;

use App\Enums\Permission;
use App\Enums\ReportType;
use App\Models\TransactionStatus;

class PermissionRegistry
{
    /** @return array<string, list<PermissionOption>> */
    public static function grouped(bool $assignableOnly = true): array
    {
        $groups = [];
        $superAdminOnly = array_flip(Permission::superAdminOnlyValues());

        foreach (Permission::grouped() as $group => $permissions) {
            if ($assignableOnly) {
                $permissions = array_values(array_filter(
                    $permissions,
                    fn (Permission $permission) => ! isset($superAdminOnly[$permission->value]),
                ));
            }

            if ($permissions === []) {
                continue;
            }

            $options = array_map(
                fn (Permission $permission) => new PermissionOption($permission->value, $permission->label()),
                $permissions
            );

            if ($group === __('permissions.groups.reports')) {
                $options = array_merge($options, self::reportPermissionOptions());
            }

            $groups[$group] = $options;
        }

        $workflow = TransactionStatus::workflowPermissionOptions();

        if ($workflow !== []) {
            $groups[__('permissions.groups.workflow_stages')] = $workflow;
        }

        return $groups;
    }

    /** @return list<string> */
    public static function assignableValues(): array
    {
        $blocked = array_flip(Permission::superAdminOnlyValues());

        return array_values(array_filter(
            self::allValues(),
            fn (string $permission) => ! isset($blocked[$permission]),
        ));
    }

    /** @return list<PermissionOption> */
    private static function reportPermissionOptions(): array
    {
        return array_map(
            fn (ReportType $type) => new PermissionOption($type->permission(), $type->permissionLabel()),
            ReportType::cases()
        );
    }

    /** @return list<string> */
    public static function allValues(): array
    {
        return array_values(array_unique(array_merge(
            Permission::values(),
            ReportType::permissionValues(),
            TransactionStatus::workflowPermissionKeys(),
        )));
    }

    public static function labelFor(string $permission): string
    {
        $enum = Permission::tryFrom($permission);

        if ($enum) {
            return $enum->label();
        }

        foreach (ReportType::cases() as $type) {
            if ($type->permission() === $permission) {
                return $type->permissionLabel();
            }
        }

        $status = TransactionStatus::findByWorkflowPermission($permission);

        if ($status) {
            return $status->workflowPermissionLabel();
        }

        return $permission;
    }
}
