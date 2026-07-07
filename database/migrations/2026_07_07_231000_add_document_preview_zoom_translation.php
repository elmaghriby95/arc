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
            ['group' => 'documents', 'key' => 'preview_zoom', 'values' => ['ar' => 'تكبير المستند', 'en' => 'Document zoom', 'fr' => 'Zoom du document']],
            ['group' => 'documents', 'key' => 'preview_resize_hint', 'values' => ['ar' => 'استخدم شريط التكبير لجعل المستند أوضح داخل منطقة المعاينة.', 'en' => 'Use the zoom slider to make the document clearer inside the preview area.', 'fr' => 'Utilisez le curseur de zoom pour rendre le document plus lisible dans la zone d\'aperçu.']],
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

                if ($entry['key'] === 'preview_zoom') {
                    $translationKey->delete();
                }
            }
        }
    }
};
