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
            ['group' => 'settings.txn_statuses', 'key' => 'visibility_scope', 'values' => [
                'ar' => 'نطاق رؤية المرحلة',
                'en' => 'Stage visibility scope',
                'fr' => 'Portée de visibilité',
            ]],
            ['group' => 'settings.txn_statuses', 'key' => 'visibility_scope_unit', 'values' => [
                'ar' => 'الوحدة التنظيمية فقط',
                'en' => 'Organizational unit only',
                'fr' => 'Unité organisationnelle uniquement',
            ]],
            ['group' => 'settings.txn_statuses', 'key' => 'visibility_scope_global', 'values' => [
                'ar' => 'كل الوحدات التنظيمية',
                'en' => 'All organizational units',
                'fr' => 'Toutes les unités',
            ]],
            ['group' => 'settings.txn_statuses', 'key' => 'visibility_scope_hint', 'values' => [
                'ar' => 'يحدد من يرى المعاملات في هذه المرحلة: ضمن وحدته أو من كل الوحدات.',
                'en' => 'Defines who sees transactions at this stage: within their unit or across all units.',
                'fr' => 'Définit qui voit les transactions à cette étape.',
            ]],
            ['group' => 'settings.txn_statuses', 'key' => 'badge_scope_unit', 'values' => [
                'ar' => 'نطاق: الوحدة',
                'en' => 'Scope: unit',
                'fr' => 'Portée : unité',
            ]],
            ['group' => 'settings.txn_statuses', 'key' => 'badge_scope_global', 'values' => [
                'ar' => 'نطاق: كل الوحدات',
                'en' => 'Scope: all units',
                'fr' => 'Portée : toutes les unités',
            ]],
        ];
    }

    public function up(): void
    {
        foreach ($this->entries() as $entry) {
            $key = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
            );

            foreach ($entry['values'] as $locale => $value) {
                $language = Language::query()->where('code', $locale)->first();

                if (! $language) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    [
                        'translation_key_id' => $key->id,
                        'language_id' => $language->id,
                    ],
                    ['value' => $value],
                );
            }
        }
    }

    public function down(): void
    {
        foreach ($this->entries() as $entry) {
            $key = TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->first();

            if (! $key) {
                continue;
            }

            Translation::query()->where('translation_key_id', $key->id)->delete();
            $key->delete();
        }
    }
};
