<?php

use App\Models\Role;
use App\Models\TransactionStatus;
use App\Services\WorkflowPermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $legacyMap = [
        'transactions.status.review' => 'transactions.workflow.review',
        'transactions.status.approve' => 'transactions.workflow.approved',
        'transactions.status.archive' => 'transactions.workflow.archived',
    ];

    public function up(): void
    {
        $workflowPermissions = app(WorkflowPermissionService::class);

        foreach (TransactionStatus::query()->where('is_initial', false)->get() as $status) {
            $workflowPermissions->sync($status, $status->required_permission);
        }

        foreach (Role::all() as $role) {
            $permissions = $role->permissions ?? [];
            $changed = false;

            foreach ($this->legacyMap as $old => $new) {
                if (! in_array($old, $permissions, true)) {
                    continue;
                }

                $permissions = array_values(array_diff($permissions, [$old]));

                if (! in_array($new, $permissions, true)) {
                    $permissions[] = $new;
                }

                $changed = true;
            }

            if ($changed) {
                $role->update([
                    'permissions' => Role::normalizePermissions($permissions),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Workflow permissions are not reverted.
    }
};
