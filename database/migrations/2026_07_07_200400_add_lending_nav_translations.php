<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @return list<array{group: string, key: string, values: array<string, string>}> */
    private function entries(): array
    {
        return [
            ['group' => 'nav', 'key' => 'lending_requests', 'values' => [
                'ar' => 'طلبات الإعارة',
                'en' => 'Lending requests',
                'fr' => 'Demandes de prêt',
            ]],
            ['group' => 'nav', 'key' => 'archive', 'values' => [
                'ar' => 'الأرشفة',
                'en' => 'Archive',
                'fr' => 'Archives',
            ]],
            ['group' => 'nav', 'key' => 'structure', 'values' => [
                'ar' => 'الهيكل',
                'en' => 'Structure',
                'fr' => 'Structure',
            ]],
            ['group' => 'nav', 'key' => 'more', 'values' => [
                'ar' => 'المزيد',
                'en' => 'More',
                'fr' => 'Plus',
            ]],
        ];
    }

    public function up(): void
    {
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
