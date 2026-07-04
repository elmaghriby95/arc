<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'مدير النظام',
                'description' => 'صلاحيات كاملة على جميع أجزاء النظام',
                'permissions' => Permission::values(),
                'is_system' => true,
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@arc.local'],
            [
                'name' => 'مدير النظام',
                'password' => 'password',
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]
        );

        if ($admin->role_id !== $adminRole->id) {
            $admin->update(['role_id' => $adminRole->id]);
        }

        $this->command?->info('تم إنشاء حساب المدير بنجاح.');
        $this->command?->info('البريد: admin@arc.local');
        $this->command?->info('كلمة المرور: password');
    }
}
