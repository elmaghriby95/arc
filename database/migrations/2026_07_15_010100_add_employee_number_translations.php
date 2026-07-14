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
            ['group' => 'settings.users', 'key' => 'employee_number', 'values' => [
                'ar' => 'الرقم الوظيفي',
                'en' => 'Employee number',
                'fr' => 'Matricule',
            ]],
            ['group' => 'settings.users', 'key' => 'employee_number_placeholder', 'values' => [
                'ar' => 'مثال: 12345',
                'en' => 'e.g. 12345',
                'fr' => 'ex. 12345',
            ]],
            ['group' => 'profile', 'key' => 'employee_number', 'values' => [
                'ar' => 'الرقم الوظيفي',
                'en' => 'Employee number',
                'fr' => 'Matricule',
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
