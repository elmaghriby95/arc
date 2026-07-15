<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Models\Role;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Language;
use App\Models\Tag;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchiveSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedReferenceData();
        $departments = $this->seedDepartments();
        $this->assignFolderDepartments($departments);
        $users = $this->seedUsers($departments);
        $tags = $this->seedTags();
        $this->seedDocuments($departments, $users, $tags);
        $this->seedAuditLogs($users);
    }

    private function seedReferenceData(): void
    {
        $documentTypes = [
            ['name' => 'خطاب', 'code' => 'LETTER', 'description' => 'خطابات رسمية صادرة وواردة', 'sort_order' => 1],
            ['name' => 'عقد', 'code' => 'CONTRACT', 'description' => 'عقود واتفاقيات', 'sort_order' => 2],
            ['name' => 'تقرير', 'code' => 'REPORT', 'description' => 'تقارير إدارية ومالية', 'sort_order' => 3],
            ['name' => 'قرار', 'code' => 'DECISION', 'description' => 'قرارات إدارية', 'sort_order' => 4],
            ['name' => 'محضر', 'code' => 'MINUTES', 'description' => 'محاضر اجتماعات', 'sort_order' => 5],
            ['name' => 'فاتورة', 'code' => 'INVOICE', 'description' => 'فواتير ومستندات مالية', 'sort_order' => 6],
        ];

        foreach ($documentTypes as $type) {
            DocumentType::firstOrCreate(['code' => $type['code']], [...$type, 'is_active' => true]);
        }

        $transactionTypes = [
            ['name' => 'معاملة واردة', 'code' => 'IN', 'description' => 'معاملات مستلمة من جهات خارجية', 'sort_order' => 1],
            ['name' => 'معاملة صادرة', 'code' => 'OUT', 'description' => 'معاملات مرسلة لجهات خارجية', 'sort_order' => 2],
            ['name' => 'معاملة داخلية', 'code' => 'INT', 'description' => 'معاملات بين الأقسام', 'sort_order' => 3],
            ['name' => 'طلب خدمة', 'code' => 'SRV', 'description' => 'طلبات الخدمات الداخلية', 'sort_order' => 4],
            ['name' => 'شكوى', 'code' => 'CMP', 'description' => 'شكاوى العملاء والمراجعين', 'sort_order' => 5],
        ];

        foreach ($transactionTypes as $type) {
            TransactionType::firstOrCreate(['code' => $type['code']], [...$type, 'is_active' => true]);
        }

        $root = Folder::firstOrCreate(
            ['name' => 'الأرشيف الرئيسي'],
            ['description' => 'المجلد الجذري لجميع الوثائق', 'color' => '#4338ca', 'sort_order' => 1, 'is_active' => true]
        );

        $folders = [
            ['name' => 'مراسلات', 'description' => 'مجلد المراسلات الرسمية', 'color' => '#0ea5e9', 'sort_order' => 1],
            ['name' => 'عقود', 'description' => 'مجلد العقود والاتفاقيات', 'color' => '#8b5cf6', 'sort_order' => 2],
            ['name' => 'تقارير', 'description' => 'مجلد التقارير الدورية', 'color' => '#10b981', 'sort_order' => 3],
            ['name' => 'قرارات', 'description' => 'مجلد القرارات الإدارية', 'color' => '#f59e0b', 'sort_order' => 4],
        ];

        foreach ($folders as $folder) {
            Folder::firstOrCreate(
                ['name' => $folder['name'], 'parent_id' => $root->id],
                [...$folder, 'is_active' => true]
            );
        }

        Folder::firstOrCreate(
            ['name' => 'خطابات صادرة', 'parent_id' => Folder::where('name', 'مراسلات')->value('id')],
            ['description' => 'الخطابات الصادرة', 'color' => '#38bdf8', 'sort_order' => 1, 'is_active' => true]
        );

        Folder::firstOrCreate(
            ['name' => 'خطابات واردة', 'parent_id' => Folder::where('name', 'مراسلات')->value('id')],
            ['description' => 'الخطابات الواردة', 'color' => '#0284c7', 'sort_order' => 2, 'is_active' => true]
        );

        $languages = [
            ['name' => 'العربية', 'code' => 'ar', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_default' => true],
            ['name' => 'English', 'code' => 'en', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => false],
            ['name' => 'Français', 'code' => 'fr', 'native_name' => 'Français', 'direction' => 'ltr', 'is_default' => false],
        ];

        foreach ($languages as $language) {
            Language::firstOrCreate(
                ['code' => $language['code']],
                [...$language, 'is_active' => true]
            );
        }
    }

    private function assignFolderDepartments(array $departments): void
    {
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
            Folder::where('name', $folderName)->update([
                'department_id' => $departments[$departmentCode]->id,
            ]);
        }

        $departmentFolders = [
            'IT' => ['name' => 'أرشيف تقنية المعلومات', 'description' => 'مجلد وثائق قسم تقنية المعلومات', 'color' => '#6366f1', 'sort_order' => 5],
            'CS' => ['name' => 'أرشيف خدمات العملاء', 'description' => 'مجلد وثائق قسم خدمات العملاء', 'color' => '#ec4899', 'sort_order' => 6],
        ];

        foreach ($departmentFolders as $code => $folder) {
            Folder::firstOrCreate(
                ['name' => $folder['name']],
                [
                    ...$folder,
                    'department_id' => $departments[$code]->id,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDepartments(): array
    {
        $general = Department::firstOrCreate(
            ['code' => 'GEN'],
            [
                'name' => 'الإدارة العامة',
                'unit_label' => 'إدارة',
                'description' => 'الإدارة العامة للمؤسسة',
                'is_active' => true,
            ]
        );

        $children = [
            ['name' => 'الموارد البشرية', 'code' => 'HR', 'unit_label' => 'قسم', 'description' => 'شؤون الموظفين والتوظيف'],
            ['name' => 'المالية', 'code' => 'FIN', 'unit_label' => 'قسم', 'description' => 'الشؤون المالية والمحاسبة'],
            ['name' => 'تقنية المعلومات', 'code' => 'IT', 'unit_label' => 'قسم', 'description' => 'الدعم التقني والأنظمة'],
            ['name' => 'الشؤون القانونية', 'code' => 'LEG', 'unit_label' => 'قسم', 'description' => 'العقود والاستشارات القانونية'],
            ['name' => 'خدمات العملاء', 'code' => 'CS', 'unit_label' => 'قسم', 'description' => 'الدعم والعلاقات مع العملاء'],
        ];

        $map = ['GEN' => $general];

        foreach ($children as $department) {
            $map[$department['code']] = Department::firstOrCreate(
                ['code' => $department['code']],
                [
                    ...$department,
                    'parent_id' => $general->id,
                    'is_active' => true,
                ]
            );
        }

        return $map;
    }

    private function seedUsers(array $departments): array
    {
        $roles = Role::pluck('id', 'slug');

        $accounts = [
            [
                'name' => 'مدير النظام',
                'email' => 'admin@arc.local',
                'role' => 'admin',
                'department' => 'IT',
            ],
            [
                'name' => 'أحمد المنصوري',
                'email' => 'manager.hr@arc.local',
                'role' => 'manager',
                'department' => 'HR',
            ],
            [
                'name' => 'سارة العلي',
                'email' => 'manager.fin@arc.local',
                'role' => 'manager',
                'department' => 'FIN',
            ],
            [
                'name' => 'محمد الحسن',
                'email' => 'user.it@arc.local',
                'role' => 'user',
                'department' => 'IT',
            ],
            [
                'name' => 'فاطمة الزهراني',
                'email' => 'user.leg@arc.local',
                'role' => 'user',
                'department' => 'LEG',
            ],
            [
                'name' => 'خالد الشمري',
                'email' => 'user.cs@arc.local',
                'role' => 'user',
                'department' => 'CS',
            ],
        ];

        $map = [];

        foreach ($accounts as $account) {
            $map[$account['email']] = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $roles[$account['role']] ?? $roles['user'],
                    'department_id' => $departments[$account['department']]->id,
                    'email_verified_at' => now(),
                ]
            );
        }

        return $map;
    }

    private function seedTags(): array
    {
        $names = [
            'رسمي',
            'عاجل',
            'داخلي',
            'خارجي',
            'سري',
            'يتطلب متابعة',
            'معتمد',
            'مسودة',
        ];

        $map = [];

        foreach ($names as $name) {
            $map[$name] = Tag::firstOrCreate(['name' => $name]);
        }

        return $map;
    }

    private function seedDocuments(array $departments, array $users, array $tags): void
    {
        $samples = [
            [
                'title' => 'خطاب تعيين موظف جديد',
                'description' => 'خطاب رسمي بشأن تعيين موظف في قسم الموارد البشرية.',
                'department' => 'HR',
                'uploader' => 'manager.hr@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(12),
                'tag_names' => ['رسمي', 'معتمد'],
                'versions' => 1,
            ],
            [
                'title' => 'عقد توريد أجهزة حاسوب',
                'description' => 'عقد توريد أجهزة حاسوب وملحقاتها لقسم تقنية المعلومات.',
                'department' => 'IT',
                'uploader' => 'admin@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(30),
                'tag_names' => ['رسمي', 'خارجي'],
                'versions' => 2,
            ],
            [
                'title' => 'تقرير مالي ربع سنوي',
                'description' => 'تقرير الأداء المالي للربع الأول من السنة.',
                'department' => 'FIN',
                'uploader' => 'manager.fin@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => true,
                'document_date' => now()->subDays(45),
                'tag_names' => ['سري', 'داخلي', 'معتمد'],
                'versions' => 1,
            ],
            [
                'title' => 'قرار تشكيل لجنة الأرشفة',
                'description' => 'قرار إداري بتشكيل لجنة متابعة مشروع الأرشفة الإلكترونية.',
                'department' => 'GEN',
                'uploader' => 'admin@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(60),
                'tag_names' => ['رسمي', 'معتمد'],
                'versions' => 1,
            ],
            [
                'title' => 'محضر اجتماع مجلس الإدارة',
                'description' => 'محضر اجتماع مجلس الإدارة الدوري الشهري.',
                'department' => 'GEN',
                'uploader' => 'admin@arc.local',
                'status' => DocumentStatus::Archived,
                'is_confidential' => true,
                'document_date' => now()->subDays(90),
                'tag_names' => ['سري', 'داخلي'],
                'versions' => 1,
            ],
            [
                'title' => 'مذكرة استشارة قانونية',
                'description' => 'مذكرة قانونية بخصوص مراجعة بنود عقد الخدمات.',
                'department' => 'LEG',
                'uploader' => 'user.leg@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => true,
                'document_date' => now()->subDays(8),
                'tag_names' => ['سري', 'يتطلب متابعة'],
                'versions' => 1,
            ],
            [
                'title' => 'خطاب شكوى عميل',
                'description' => 'خطاب وارد من عميل يتضمن شكوى بخصوص الخدمة المقدمة.',
                'department' => 'CS',
                'uploader' => 'user.cs@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(3),
                'tag_names' => ['عاجل', 'خارجي', 'يتطلب متابعة'],
                'versions' => 1,
            ],
            [
                'title' => 'سياسة أمن المعلومات',
                'description' => 'النسخة المعتمدة من سياسة أمن المعلومات والبيانات.',
                'department' => 'IT',
                'uploader' => 'user.it@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(120),
                'tag_names' => ['رسمي', 'داخلي', 'معتمد'],
                'versions' => 3,
            ],
            [
                'title' => 'طلب إجازة موظف',
                'description' => 'نموذج طلب إجازة سنوية لأحد موظفي القسم.',
                'department' => 'HR',
                'uploader' => 'manager.hr@arc.local',
                'status' => DocumentStatus::Draft,
                'is_confidential' => false,
                'document_date' => now()->subDay(),
                'tag_names' => ['مسودة', 'داخلي'],
                'versions' => 1,
            ],
            [
                'title' => 'تقرير ميزانية سنوي',
                'description' => 'مسودة التقرير السنوي للميزانية قبل الاعتماد النهائي.',
                'department' => 'FIN',
                'uploader' => 'manager.fin@arc.local',
                'status' => DocumentStatus::Draft,
                'is_confidential' => true,
                'document_date' => now()->subDays(2),
                'tag_names' => ['مسودة', 'سري'],
                'versions' => 1,
            ],
            [
                'title' => 'اتفاقية سرية مع مورد',
                'description' => 'اتفاقية عدم إفشاء مع أحد الموردين الخارجيين.',
                'department' => 'LEG',
                'uploader' => 'user.leg@arc.local',
                'status' => DocumentStatus::Archived,
                'is_confidential' => true,
                'document_date' => now()->subDays(200),
                'tag_names' => ['سري', 'خارجي', 'معتمد'],
                'versions' => 1,
            ],
            [
                'title' => 'تقرير رضا العملاء',
                'description' => 'نتائج استبيان رضا العملاء للنصف الأول من السنة.',
                'department' => 'CS',
                'uploader' => 'user.cs@arc.local',
                'status' => DocumentStatus::Active,
                'is_confidential' => false,
                'document_date' => now()->subDays(20),
                'tag_names' => ['رسمي', 'داخلي'],
                'versions' => 1,
            ],
        ];

        foreach ($samples as $index => $sample) {
            $referenceNumber = $this->referenceNumber($index + 1);

            if (Document::where('reference_number', $referenceNumber)->exists()) {
                continue;
            }

            $fileName = Str::slug($sample['title']).'.txt';
            $filePath = 'documents/seed/'.($index + 1).'-'.$fileName;
            $content = $this->buildSampleFileContent($sample);

            Storage::disk('local')->put($filePath, $content);
            $fileSize = strlen($content);

            $document = Document::create([
                'reference_number' => $referenceNumber,
                'title' => $sample['title'],
                'description' => $sample['description'],
                'department_id' => $departments[$sample['department']]->id,
                'uploaded_by' => $users[$sample['uploader']]->id,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'mime_type' => 'text/plain',
                'document_date' => $sample['document_date'],
                'status' => $sample['status'],
                'is_confidential' => $sample['is_confidential'],
            ]);

            $tagIds = collect($sample['tag_names'])
                ->map(fn (string $name) => $tags[$name]->id)
                ->all();

            $document->tags()->sync($tagIds);

            for ($version = 1; $version <= $sample['versions']; $version++) {
                $versionPath = $filePath;
                $versionName = $fileName;
                $versionSize = $fileSize;
                $changeNote = $version === 1 ? 'الإصدار الأول' : 'تحديث المحتوى - الإصدار '.$version;

                if ($version > 1) {
                    $versionPath = 'documents/seed/'.($index + 1).'-v'.$version.'-'.$fileName;
                    $versionContent = $content."\n\n--- الإصدار {$version} ---\nتم تحديث الوثيقة.";
                    Storage::disk('local')->put($versionPath, $versionContent);
                    $versionSize = strlen($versionContent);
                    $versionName = 'v'.$version.'-'.$fileName;
                }

                $document->versions()->create([
                    'version_number' => $version,
                    'file_path' => $versionPath,
                    'file_name' => $versionName,
                    'file_size' => $versionSize,
                    'uploaded_by' => $users[$sample['uploader']]->id,
                    'change_note' => $changeNote,
                    'created_at' => now()->subDays(max(1, 12 - $version)),
                    'updated_at' => now()->subDays(max(1, 12 - $version)),
                ]);

                if ($version === $sample['versions']) {
                    $document->update([
                        'file_path' => $versionPath,
                        'file_name' => $versionName,
                        'file_size' => $versionSize,
                    ]);
                }
            }
        }
    }

    private function seedAuditLogs(array $users): void
    {
        $admin = $users['admin@arc.local'];
        $documents = Document::with('uploader')->limit(5)->get();

        foreach ($documents as $document) {
            AuditLog::firstOrCreate(
                [
                    'auditable_type' => Document::class,
                    'auditable_id' => $document->id,
                    'action' => 'created',
                ],
                [
                    'user_id' => $admin->id,
                    'old_values' => null,
                    'new_values' => [
                        'title' => $document->title,
                        'reference_number' => $document->reference_number,
                        'status' => $document->status->value,
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'ArchiveSeeder/1.0',
                    'created_at' => $document->created_at,
                    'updated_at' => $document->created_at,
                ]
            );
        }
    }

    private function referenceNumber(int $sequence): string
    {
        return 'ARC-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function buildSampleFileContent(array $sample): string
    {
        return implode("\n", [
            'منظومة الأرشفة الإلكترونية',
            '========================',
            'العنوان: '.$sample['title'],
            'الوصف: '.$sample['description'],
            'الحالة: '.$sample['status']->label(),
            'تاريخ الوثيقة: '.$sample['document_date']->format('Y-m-d'),
            '',
            'هذا ملف تجريبي تم إنشاؤه تلقائياً بواسطة السيدر.',
        ]);
    }
}
