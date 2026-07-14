<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return list<array{group: string, key: string, values: array<string, string>}> */
    private function entries(): array
    {
        return [
            ['group' => 'reports', 'key' => 'ops.audit.folder.created', 'values' => [
                'ar' => 'إنشاء مجلد', 'en' => 'Folder created', 'fr' => 'Dossier créé',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.folder.updated', 'values' => [
                'ar' => 'تعديل مجلد', 'en' => 'Folder updated', 'fr' => 'Dossier modifié',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.folder.deleted', 'values' => [
                'ar' => 'حذف مجلد', 'en' => 'Folder deleted', 'fr' => 'Dossier supprimé',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.department.created', 'values' => [
                'ar' => 'إنشاء إدارة / وحدة', 'en' => 'Department created', 'fr' => 'Département créé',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.department.updated', 'values' => [
                'ar' => 'تعديل إدارة / وحدة', 'en' => 'Department updated', 'fr' => 'Département modifié',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.department.deleted', 'values' => [
                'ar' => 'حذف إدارة / وحدة', 'en' => 'Department deleted', 'fr' => 'Département supprimé',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.admin.user.created', 'values' => [
                'ar' => 'إنشاء مستخدم', 'en' => 'User created', 'fr' => 'Utilisateur créé',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.admin.user.updated', 'values' => [
                'ar' => 'تعديل مستخدم', 'en' => 'User updated', 'fr' => 'Utilisateur modifié',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.admin.user.deleted', 'values' => [
                'ar' => 'حذف مستخدم', 'en' => 'User deleted', 'fr' => 'Utilisateur supprimé',
            ]],
            ['group' => 'reports', 'key' => 'ops.page_summary', 'values' => [
                'ar' => 'عرض :shown من :total — الصفحة :page من :pages',
                'en' => 'Showing :shown of :total — page :page of :pages',
                'fr' => 'Affichage :shown sur :total — page :page sur :pages',
            ]],
            ['group' => 'reports', 'key' => 'ops.pdf_limit_note', 'values' => [
                'ar' => 'ملف PDF يعرض أحدث :shown عملية من أصل :total (حد أقصى :limit لضمان استقرار التصدير). استخدم Excel للسجل الكامل.',
                'en' => 'PDF shows the latest :shown of :total events (max :limit for stability). Use Excel for the full log.',
                'fr' => 'Le PDF affiche les :shown derniers sur :total (max :limit). Utilisez Excel pour le journal complet.',
            ]],
            ['group' => 'reports', 'key' => 'ops.collect_cap_note', 'values' => [
                'ar' => 'تم الحدّ بنتائج البحث إلى أحدث :limit عملية. ضيّق الفلاتر لرؤية نطاق أدق.',
                'en' => 'Results capped to the latest :limit events. Narrow filters for a more precise range.',
                'fr' => 'Résultats limités aux :limit derniers événements. Affinez les filtres.',
            ]],
            ['group' => 'settings.users', 'key' => 'delete', 'values' => [
                'ar' => 'حذف المستخدم', 'en' => 'Delete user', 'fr' => 'Supprimer l\'utilisateur',
            ]],
            ['group' => 'settings.users', 'key' => 'delete_confirm', 'values' => [
                'ar' => 'هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.',
                'en' => 'Are you sure you want to delete this user? This cannot be undone.',
                'fr' => 'Voulez-vous vraiment supprimer cet utilisateur ? Cette action est irréversible.',
            ]],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('translation_keys') || ! Schema::hasTable('translations') || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');
        if ($languages->isEmpty()) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
            );

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $translationKey->id, 'language_id' => $languages[$code]],
                    ['value' => $value],
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('translation_keys') || ! Schema::hasTable('translations')) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $translationKey = TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->first();

            if ($translationKey) {
                Translation::query()->where('translation_key_id', $translationKey->id)->delete();
                $translationKey->delete();
            }
        }
    }
};
