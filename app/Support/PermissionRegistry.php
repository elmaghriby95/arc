<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\TransactionStatus;

class PermissionRegistry
{
    /** @return array<string, list<PermissionOption>> */
    public static function grouped(): array
    {
        $groups = [];

        foreach (Permission::grouped() as $group => $permissions) {
            $groups[$group] = array_map(
                fn (Permission $permission) => new PermissionOption($permission->value, $permission->label()),
                $permissions
            );
        }

        $workflow = TransactionStatus::workflowPermissionOptions();

        if ($workflow !== []) {
            $groups['سير عمل المعاملات (مراحل)'] = $workflow;
        }

        return $groups;
    }

    /** @return list<string> */
    public static function allValues(): array
    {
        return array_values(array_unique(array_merge(
            Permission::values(),
            TransactionStatus::workflowPermissionKeys(),
        )));
    }

    public static function labelFor(string $permission): string
    {
        $enum = Permission::tryFrom($permission);

        if ($enum) {
            return $enum->label();
        }

        $status = TransactionStatus::findByWorkflowPermission($permission);

        if ($status) {
            return $status->workflowPermissionLabel();
        }

        return $permission;
    }
}
