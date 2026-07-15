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
            ['group' => 'settings.users', 'key' => 'filters_title', 'values' => [
                'ar' => 'بحث المستخدمين',
                'en' => 'Search users',
                'fr' => 'Rechercher des utilisateurs',
            ]],
            ['group' => 'settings.users', 'key' => 'filters_subtitle', 'values' => [
                'ar' => 'ابحث في الاسم، البريد الإلكتروني، الرقم الوظيفي، الدور، اللغة، والوحدة التنظيمية.',
                'en' => 'Search by name, email, employee number, role, language, and organizational unit.',
                'fr' => 'Recherchez par nom, e-mail, matricule, rôle, langue et unité organisationnelle.',
            ]],
            ['group' => 'settings.users', 'key' => 'search_placeholder', 'values' => [
                'ar' => 'ابحث في كل حقول المستخدمين...',
                'en' => 'Search all user fields...',
                'fr' => 'Rechercher dans tous les champs utilisateur...',
            ]],
            ['group' => 'settings.users', 'key' => 'no_search_results', 'values' => [
                'ar' => 'لا توجد نتائج مطابقة للبحث.',
                'en' => 'No users match this search.',
                'fr' => 'Aucun utilisateur ne correspond à cette recherche.',
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
