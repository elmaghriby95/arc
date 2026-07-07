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
            ['group' => 'settings', 'key' => 'qr_code_title', 'values' => ['ar' => 'إعدادات رمز QR', 'en' => 'QR Code Settings', 'fr' => 'Paramètres du code QR']],
            ['group' => 'settings', 'key' => 'qr_code_desc', 'values' => ['ar' => 'التحكم في حجم رمز QR للمعاملات في العرض والطباعة', 'en' => 'Control transaction QR code size for display and printing', 'fr' => 'Contrôler la taille du QR pour l\'affichage et l\'impression']],
            ['group' => 'settings', 'key' => 'qr_code_badge', 'values' => ['ar' => 'حجم العرض والطباعة', 'en' => 'Display & print size', 'fr' => 'Taille affichage et impression']],
            ['group' => 'settings.qr_code', 'key' => 'title', 'values' => ['ar' => 'إعدادات رمز QR', 'en' => 'QR Code Settings', 'fr' => 'Paramètres du code QR']],
            ['group' => 'settings.qr_code', 'key' => 'subtitle', 'values' => ['ar' => 'تحديد حجم رمز QR في صفحة المعاملة وعند الطباعة', 'en' => 'Set QR code size on the transaction page and when printing', 'fr' => 'Définir la taille du QR sur la page transaction et à l\'impression']],
            ['group' => 'settings.qr_code', 'key' => 'info', 'values' => ['ar' => 'يُستخدم رمز QR لربط المعاملة بموقعها الفيزيائي في الأرشيف. يمكنك ضبط حجم العرض في الشريط الجانبي وحجم الطباعة بشكل مستقل.', 'en' => 'The QR code links the transaction to its physical archive location. You can set display and print sizes independently.', 'fr' => 'Le QR relie la transaction à son emplacement physique. Tailles d\'affichage et d\'impression indépendantes.']],
            ['group' => 'settings.qr_code', 'key' => 'sizes_title', 'values' => ['ar' => 'أحجام رمز QR', 'en' => 'QR code sizes', 'fr' => 'Tailles du code QR']],
            ['group' => 'settings.qr_code', 'key' => 'display_size', 'values' => ['ar' => 'حجم العرض (بكسل)', 'en' => 'Display size (px)', 'fr' => 'Taille d\'affichage (px)']],
            ['group' => 'settings.qr_code', 'key' => 'display_size_desc', 'values' => ['ar' => 'يظهر في الشريط الجانبي لصفحة المعاملة', 'en' => 'Shown in the transaction page sidebar', 'fr' => 'Affiché dans la barre latérale de la transaction']],
            ['group' => 'settings.qr_code', 'key' => 'print_size', 'values' => ['ar' => 'حجم الطباعة (بكسل)', 'en' => 'Print size (px)', 'fr' => 'Taille d\'impression (px)']],
            ['group' => 'settings.qr_code', 'key' => 'print_size_desc', 'values' => ['ar' => 'يُستخدم عند طباعة رمز QR', 'en' => 'Used when printing the QR code', 'fr' => 'Utilisé lors de l\'impression du QR']],
            ['group' => 'settings.qr_code', 'key' => 'size_hint', 'values' => ['ar' => 'القيم المسموحة: من 80 إلى 500 بكسل', 'en' => 'Allowed range: 80 to 500 pixels', 'fr' => 'Plage autorisée : 80 à 500 pixels']],
            ['group' => 'settings.qr_code', 'key' => 'save', 'values' => ['ar' => 'حفظ الإعدادات', 'en' => 'Save settings', 'fr' => 'Enregistrer les paramètres']],
            ['group' => 'settings.qr_code', 'key' => 'readonly', 'values' => ['ar' => 'لديك صلاحية العرض فقط — لا يمكنك تعديل هذه الإعدادات.', 'en' => 'View-only access — you cannot edit these settings.', 'fr' => 'Accès lecture seule — modification impossible.']],
            ['group' => 'messages', 'key' => 'transaction_qr.saved', 'values' => ['ar' => 'تم حفظ إعدادات رمز QR بنجاح.', 'en' => 'QR code settings saved successfully.', 'fr' => 'Paramètres du QR enregistrés avec succès.']],
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
