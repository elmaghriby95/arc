<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('transaction_statuses')->insert([
            [
                'name' => 'مسودة',
                'code' => 'DRAFT',
                'description' => 'معاملة قيد الإعداد ولم تُرسل بعد',
                'sort_order' => 1,
                'required_permission' => null,
                'color' => '#94a3b8',
                'is_initial' => true,
                'is_final' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'قيد المراجعة',
                'code' => 'REVIEW',
                'description' => 'المعاملة بانتظار المراجعة',
                'sort_order' => 2,
                'required_permission' => Permission::TransactionsStatusReview->value,
                'color' => '#f59e0b',
                'is_initial' => false,
                'is_final' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'معتمد',
                'code' => 'APPROVED',
                'description' => 'تم اعتماد المعاملة',
                'sort_order' => 3,
                'required_permission' => Permission::TransactionsStatusApprove->value,
                'color' => '#22c55e',
                'is_initial' => false,
                'is_final' => false,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'مؤرشف',
                'code' => 'ARCHIVED',
                'description' => 'المعاملة مكتملة ومؤرشفة',
                'sort_order' => 4,
                'required_permission' => Permission::TransactionsStatusArchive->value,
                'color' => '#6366f1',
                'is_initial' => false,
                'is_final' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $newPermissions = [
            Permission::TransactionsView->value,
            Permission::TransactionsCreate->value,
            Permission::TransactionsEdit->value,
            Permission::TransactionsDelete->value,
            Permission::TransactionsStatusReview->value,
            Permission::TransactionsStatusApprove->value,
            Permission::TransactionsStatusArchive->value,
            Permission::SettingsTransactionStatusesView->value,
            Permission::SettingsTransactionStatusesCreate->value,
            Permission::SettingsTransactionStatusesEdit->value,
            Permission::SettingsTransactionStatusesDelete->value,
        ];

        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $permissions = array_values(array_unique(array_merge($admin->permissions ?? [], $newPermissions)));
            $admin->update(['permissions' => $permissions]);
        }

        $manager = Role::where('slug', 'manager')->first();
        if ($manager) {
            $managerPerms = array_values(array_unique(array_merge($manager->permissions ?? [], [
                Permission::TransactionsView->value,
                Permission::TransactionsCreate->value,
                Permission::TransactionsEdit->value,
                Permission::TransactionsStatusReview->value,
                Permission::TransactionsStatusApprove->value,
            ])));
            $manager->update(['permissions' => $managerPerms]);
        }

        $user = Role::where('slug', 'user')->first();
        if ($user) {
            $userPerms = array_values(array_unique(array_merge($user->permissions ?? [], [
                Permission::TransactionsView->value,
                Permission::TransactionsCreate->value,
            ])));
            $user->update(['permissions' => $userPerms]);
        }
    }

    public function down(): void
    {
        DB::table('transaction_status_histories')->delete();
        DB::table('transactions')->delete();
        DB::table('transaction_statuses')->delete();

        $removePermissions = [
            Permission::TransactionsView->value,
            Permission::TransactionsCreate->value,
            Permission::TransactionsEdit->value,
            Permission::TransactionsDelete->value,
            Permission::TransactionsStatusReview->value,
            Permission::TransactionsStatusApprove->value,
            Permission::TransactionsStatusArchive->value,
            Permission::SettingsTransactionStatusesView->value,
            Permission::SettingsTransactionStatusesCreate->value,
            Permission::SettingsTransactionStatusesEdit->value,
            Permission::SettingsTransactionStatusesDelete->value,
        ];

        foreach (Role::all() as $role) {
            $permissions = array_values(array_filter(
                $role->permissions ?? [],
                fn (string $p) => ! in_array($p, $removePermissions, true)
            ));
            $role->update(['permissions' => $permissions]);
        }
    }
};
