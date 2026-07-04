<?php

use App\Models\Department;
use App\Models\Folder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $departments = Department::pluck('id', 'code');

        if ($departments->isEmpty()) {
            return;
        }

        $assignments = [
            'الأرشيف الرئيسي' => 'GEN',
            'مراسلات' => 'HR',
            'عقود' => 'LEG',
            'تقارير' => 'FIN',
            'قرارات' => 'GEN',
            'خطابات صادرة' => 'HR',
            'خطابات واردة' => 'HR',
        ];

        foreach ($assignments as $folderName => $departmentCode) {
            $departmentId = $departments->get($departmentCode);

            if ($departmentId) {
                Folder::where('name', $folderName)->whereNull('department_id')->update([
                    'department_id' => $departmentId,
                ]);
            }
        }

        $departmentFolders = [
            'IT' => ['name' => 'أرشيف تقنية المعلومات', 'description' => 'مجلد وثائق قسم تقنية المعلومات', 'color' => '#6366f1', 'sort_order' => 5],
            'CS' => ['name' => 'أرشيف خدمات العملاء', 'description' => 'مجلد وثائق قسم خدمات العملاء', 'color' => '#ec4899', 'sort_order' => 6],
        ];

        foreach ($departmentFolders as $code => $folder) {
            $departmentId = $departments->get($code);

            if ($departmentId) {
                Folder::firstOrCreate(
                    ['name' => $folder['name']],
                    [
                        ...$folder,
                        'department_id' => $departmentId,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Folder::whereIn('name', [
            'أرشيف تقنية المعلومات',
            'أرشيف خدمات العملاء',
        ])->delete();
    }
};
