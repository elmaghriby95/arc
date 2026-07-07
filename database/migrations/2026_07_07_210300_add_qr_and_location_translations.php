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
            ['group' => 'transactions', 'key' => 'archival_reference', 'values' => [
                'ar' => 'الرقم الإشاري للمعاملة',
                'en' => 'Transaction reference number',
                'fr' => 'Numéro indicatif de la transaction',
            ]],
            ['group' => 'transactions', 'key' => 'archival_reference_placeholder', 'values' => [
                'ar' => 'أدخل الرقم الإشاري الرسمي للمعاملة',
                'en' => 'Enter the official transaction reference number',
                'fr' => 'Saisir le numéro indicatif officiel',
            ]],
            ['group' => 'transactions', 'key' => 'archival_reference_hint', 'values' => [
                'ar' => 'حقل إلزامي — يُستخدم في رمز QR وفي البحث عن المعاملة',
                'en' => 'Required — used in the QR code and transaction search',
                'fr' => 'Obligatoire — utilisé dans le QR et la recherche',
            ]],
            ['group' => 'transactions', 'key' => 'qr_code_title', 'values' => [
                'ar' => 'رمز QR للمعاملة',
                'en' => 'Transaction QR code',
                'fr' => 'Code QR de la transaction',
            ]],
            ['group' => 'transactions', 'key' => 'qr_code_hint', 'values' => [
                'ar' => 'يتضمن: الرقم الإشاري | رقم الدولاب | رقم الصف | رقم الصندوق',
                'en' => 'Contains: reference | cabinet | row | box',
                'fr' => 'Contient : indicatif | armoire | rangée | boîte',
            ]],
            ['group' => 'settings.folders', 'key' => 'cabinet_number', 'values' => [
                'ar' => 'رقم الدولاب',
                'en' => 'Cabinet number',
                'fr' => 'Numéro d\'armoire',
            ]],
            ['group' => 'settings.folders', 'key' => 'row_number', 'values' => [
                'ar' => 'رقم الصف',
                'en' => 'Row number',
                'fr' => 'Numéro de rangée',
            ]],
            ['group' => 'settings.folders', 'key' => 'box_number', 'values' => [
                'ar' => 'رقم الصندوق',
                'en' => 'Box number',
                'fr' => 'Numéro de boîte',
            ]],
            ['group' => 'settings.folders', 'key' => 'cabinet_badge', 'values' => [
                'ar' => 'دولاب :number',
                'en' => 'Cabinet :number',
                'fr' => 'Armoire :number',
            ]],
            ['group' => 'settings.folders', 'key' => 'row_badge', 'values' => [
                'ar' => 'صف :number',
                'en' => 'Row :number',
                'fr' => 'Rangée :number',
            ]],
            ['group' => 'settings.folders', 'key' => 'box_badge', 'values' => [
                'ar' => 'صندوق :number',
                'en' => 'Box :number',
                'fr' => 'Boîte :number',
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
