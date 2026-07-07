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
            ['group' => 'documents', 'key' => 'preview_width', 'values' => ['ar' => 'العرض', 'en' => 'Width', 'fr' => 'Largeur']],
            ['group' => 'documents', 'key' => 'preview_height', 'values' => ['ar' => 'الارتفاع', 'en' => 'Height', 'fr' => 'Hauteur']],
            ['group' => 'documents', 'key' => 'preview_reset', 'values' => ['ar' => 'إعادة التعيين', 'en' => 'Reset', 'fr' => 'Réinitialiser']],
            ['group' => 'documents', 'key' => 'preview_resize_hint', 'values' => ['ar' => 'يمكنك سحب الزاوية السفلية لتغيير حجم المعاينة يدوياً.', 'en' => 'Drag the bottom corner to resize the preview manually.', 'fr' => 'Faites glisser le coin inférieur pour redimensionner l\'aperçu.']],
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
