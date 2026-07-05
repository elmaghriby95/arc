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
            ['group' => 'transactions', 'key' => 'scan_direct', 'values' => [
                'ar' => 'مسح مباشر',
                'en' => 'Direct scan',
                'fr' => 'Numérisation directe',
            ]],
            ['group' => 'transactions', 'key' => 'scanning', 'values' => [
                'ar' => 'جاري المسح...',
                'en' => 'Scanning...',
                'fr' => 'Numérisation...',
            ]],
            ['group' => 'transactions', 'key' => 'scan_agent_hint', 'values' => [
                'ar' => 'المسح يتطلب ARC Scan Agent على جهازك',
                'en' => 'Scanning requires ARC Scan Agent on your workstation',
                'fr' => 'La numérisation nécessite ARC Scan Agent sur votre poste',
            ]],
            ['group' => 'transactions', 'key' => 'scan_failed', 'values' => [
                'ar' => 'تعذّر المسح. تأكد أن ARC Scan Agent يعمل على جهازك.',
                'en' => 'Scan failed. Make sure ARC Scan Agent is running on your workstation.',
                'fr' => 'Échec de la numérisation. Vérifiez qu\'ARC Scan Agent est actif.',
            ]],
            ['group' => 'transactions', 'key' => 'scan_failed_detail', 'values' => [
                'ar' => 'تعذّر المسح: :message

تأكد أن ARC Scan Agent يعمل على جهازك (scan-agent/install-ubuntu.sh).',
                'en' => 'Scan failed: :message

Make sure ARC Scan Agent is running (scan-agent/install-ubuntu.sh).',
                'fr' => 'Échec : :message

Vérifiez qu\'ARC Scan Agent est actif (scan-agent/install-ubuntu.sh).',
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
