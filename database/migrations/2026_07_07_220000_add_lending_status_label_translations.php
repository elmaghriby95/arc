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
        $statusLabels = [
            'pending_review' => ['ar' => 'بانتظار المراجعة', 'en' => 'Pending review', 'fr' => 'En attente de révision'],
            'pending_handover' => ['ar' => 'بانتظار التسليم', 'en' => 'Pending handover', 'fr' => 'En attente de remise'],
            'on_loan' => ['ar' => 'معارة', 'en' => 'On loan', 'fr' => 'En prêt'],
            'returned' => ['ar' => 'مُرجعة', 'en' => 'Returned', 'fr' => 'Retournée'],
            'rejected' => ['ar' => 'مرفوضة', 'en' => 'Rejected', 'fr' => 'Rejetée'],
        ];

        $entries = [];

        foreach ($statusLabels as $key => $values) {
            $entries[] = [
                'group' => 'lending_requests.status_labels',
                'key' => $key,
                'values' => $values,
            ];
        }

        return $entries;
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
