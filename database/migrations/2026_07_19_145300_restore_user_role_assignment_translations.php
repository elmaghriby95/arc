<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return list<array{key: string, values: array<string, string>}> */
    private function entries(): array
    {
        return [
            ['key' => 'step_role', 'values' => [
                'ar' => 'الدور والموقع التنظيمي',
                'en' => 'Role and organizational location',
                'fr' => 'Rôle et emplacement organisationnel',
            ]],
            ['key' => 'role_org_title', 'values' => [
                'ar' => 'الدور والموقع التنظيمي',
                'en' => 'Role and organizational location',
                'fr' => 'Rôle et emplacement organisationnel',
            ]],
            ['key' => 'role_org_desc', 'values' => [
                'ar' => 'حدد دور المستخدم وصلاحياته وموقعه في الهيكل التنظيمي.',
                'en' => 'Set the user role, permissions, and organizational location.',
                'fr' => 'Définissez le rôle, les autorisations et l’emplacement organisationnel.',
            ]],
            ['key' => 'choose_role', 'values' => [
                'ar' => '— اختر الدور —',
                'en' => '— Choose role —',
                'fr' => '— Choisir un rôle —',
            ]],
            ['key' => 'filters_subtitle', 'values' => [
                'ar' => 'ابحث في الاسم، البريد الإلكتروني، الرقم الوظيفي، الدور، اللغة، والوحدة التنظيمية.',
                'en' => 'Search by name, email, employee number, role, language, and organizational unit.',
                'fr' => 'Recherchez par nom, e-mail, matricule, rôle, langue et unité organisationnelle.',
            ]],
            ['key' => 'edit_subtitle_full', 'values' => [
                'ar' => 'إدارة بيانات الموظف ودوره وموقعه التنظيمي وكلمة المرور',
                'en' => 'Manage employee data, role, organizational location, and password',
                'fr' => 'Gérer les données, le rôle, l’emplacement et le mot de passe',
            ]],
        ];
    }

    public function up(): void
    {
        if (! Schema::hasTable('translation_keys') || ! Schema::hasTable('translations') || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        foreach ($this->entries() as $entry) {
            $key = TranslationKey::query()->firstOrCreate([
                'group' => 'settings.users',
                'key' => $entry['key'],
            ]);

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $key->id, 'language_id' => $languages[$code]],
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

        TranslationKey::query()
            ->where('group', 'settings.users')
            ->whereIn('key', ['role_org_title', 'role_org_desc', 'choose_role'])
            ->get()
            ->each(function (TranslationKey $key): void {
                Translation::query()->where('translation_key_id', $key->id)->delete();
                $key->delete();
            });
    }
};
