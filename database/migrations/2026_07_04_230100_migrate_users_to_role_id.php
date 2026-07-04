<?php

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $allPermissions = Permission::values();

        $adminPermissions = $allPermissions;

        $managerPermissions = [
            Permission::DashboardView->value,
            Permission::DocumentsView->value,
            Permission::DocumentsCreate->value,
            Permission::DocumentsEdit->value,
            Permission::DocumentsDownload->value,
            Permission::DepartmentsView->value,
            Permission::DepartmentsCreate->value,
            Permission::DepartmentsEdit->value,
            Permission::CategoriesView->value,
            Permission::CategoriesCreate->value,
            Permission::CategoriesEdit->value,
            Permission::ProfileView->value,
            Permission::ProfileEdit->value,
        ];

        $userPermissions = [
            Permission::DashboardView->value,
            Permission::DocumentsView->value,
            Permission::DocumentsCreate->value,
            Permission::DocumentsDownload->value,
            Permission::DepartmentsView->value,
            Permission::CategoriesView->value,
            Permission::ProfileView->value,
            Permission::ProfileEdit->value,
        ];

        $roles = [
            [
                'name' => 'مدير النظام',
                'slug' => 'admin',
                'description' => 'صلاحيات كاملة على جميع أجزاء النظام',
                'permissions' => json_encode($adminPermissions),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مشرف',
                'slug' => 'manager',
                'description' => 'إدارة الوثائق والأقسام والتصنيفات',
                'permissions' => json_encode($managerPermissions),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مستخدم',
                'slug' => 'user',
                'description' => 'عرض ورفع الوثائق',
                'permissions' => json_encode($userPermissions),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
        });

        $roleMap = Role::pluck('id', 'slug');

        foreach (DB::table('users')->get() as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['role_id' => $roleMap[$user->role] ?? $roleMap['user']]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('password');
        });

        $roleMap = Role::pluck('slug', 'id');

        foreach (DB::table('users')->get() as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $roleMap[$user->role_id] ?? 'user']);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
