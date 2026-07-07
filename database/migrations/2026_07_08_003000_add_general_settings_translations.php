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
            ['group' => 'settings', 'key' => 'general_title', 'values' => ['ar' => 'الإعدادات العامة', 'en' => 'General Settings', 'fr' => 'Paramètres généraux']],
            ['group' => 'settings', 'key' => 'general_desc', 'values' => ['ar' => 'اسم المنظومة والشعار والأيقونة وإعدادات التواصل', 'en' => 'System name, logo, favicon and contact settings', 'fr' => 'Nom du système, logo, favicon et contact']],
            ['group' => 'settings', 'key' => 'general_badge', 'values' => ['ar' => 'الهوية البصرية', 'en' => 'Branding', 'fr' => 'Identité visuelle']],
            ['group' => 'settings.general', 'key' => 'title', 'values' => ['ar' => 'الإعدادات العامة للنظام', 'en' => 'General System Settings', 'fr' => 'Paramètres généraux du système']],
            ['group' => 'settings.general', 'key' => 'subtitle', 'values' => ['ar' => 'تخصيص اسم المنظومة والشعار وإعدادات العرض في الواجهة وصفحة تسجيل الدخول', 'en' => 'Customize system name, logo and display settings across the app and login page', 'fr' => 'Personnaliser le nom, le logo et l\'affichage dans l\'application et la page de connexion']],
            ['group' => 'settings.general', 'key' => 'info', 'values' => ['ar' => 'تُطبَّق هذه الإعدادات على شريط التنقل وصفحة تسجيل الدخول وعناوين الصفحات. نصوص الوصف والميزات في صفحة الدخول قابلة للتعديل من إعدادات اللغات → الترجمات.', 'en' => 'These settings apply to the navbar, login page and page titles. Login descriptions and feature texts can be edited under Languages → Translations.', 'fr' => 'Ces paramètres s\'appliquent à la barre de navigation, la page de connexion et les titres. Les textes de la page de connexion se modifient dans Langues → Traductions.']],
            ['group' => 'settings.general', 'key' => 'identity_title', 'values' => ['ar' => 'هوية المنظومة', 'en' => 'System identity', 'fr' => 'Identité du système']],
            ['group' => 'settings.general', 'key' => 'app_name', 'values' => ['ar' => 'اسم المنظومة', 'en' => 'System name', 'fr' => 'Nom du système']],
            ['group' => 'settings.general', 'key' => 'app_name_desc', 'values' => ['ar' => 'يظهر في شريط التنقل وصفحة تسجيل الدخول وعناوين الصفحات', 'en' => 'Shown in the navbar, login page and page titles', 'fr' => 'Affiché dans la barre de navigation, la page de connexion et les titres']],
            ['group' => 'settings.general', 'key' => 'app_name_placeholder', 'values' => ['ar' => 'اتركه فارغاً لاستخدام القيمة الافتراضية من ملف الإعدادات', 'en' => 'Leave empty to use the default from configuration', 'fr' => 'Laisser vide pour utiliser la valeur par défaut']],
            ['group' => 'settings.general', 'key' => 'branding_title', 'values' => ['ar' => 'الشعار والأيقونة', 'en' => 'Logo & favicon', 'fr' => 'Logo et favicon']],
            ['group' => 'settings.general', 'key' => 'logo', 'values' => ['ar' => 'شعار المنظومة', 'en' => 'System logo', 'fr' => 'Logo du système']],
            ['group' => 'settings.general', 'key' => 'logo_desc', 'values' => ['ar' => 'يظهر في شريط التنقل وصفحة تسجيل الدخول. الصيغ: PNG, JPG, WEBP, SVG — بحد أقصى 2 ميغابايت', 'en' => 'Shown in the navbar and login page. Formats: PNG, JPG, WEBP, SVG — max 2 MB', 'fr' => 'Affiché dans la barre de navigation et la page de connexion. Formats : PNG, JPG, WEBP, SVG — max 2 Mo']],
            ['group' => 'settings.general', 'key' => 'favicon', 'values' => ['ar' => 'أيقونة المتصفح (Favicon)', 'en' => 'Browser favicon', 'fr' => 'Favicon du navigateur']],
            ['group' => 'settings.general', 'key' => 'favicon_desc', 'values' => ['ar' => 'تظهر في تبويب المتصفح. الصيغ: PNG, JPG, WEBP, ICO — بحد أقصى 1 ميغابايت', 'en' => 'Shown in the browser tab. Formats: PNG, JPG, WEBP, ICO — max 1 MB', 'fr' => 'Affiché dans l\'onglet du navigateur. Formats : PNG, JPG, WEBP, ICO — max 1 Mo']],
            ['group' => 'settings.general', 'key' => 'remove_logo', 'values' => ['ar' => 'إزالة الشعار الحالي', 'en' => 'Remove current logo', 'fr' => 'Supprimer le logo actuel']],
            ['group' => 'settings.general', 'key' => 'remove_favicon', 'values' => ['ar' => 'إزالة الأيقونة الحالية', 'en' => 'Remove current favicon', 'fr' => 'Supprimer le favicon actuel']],
            ['group' => 'settings.general', 'key' => 'contact_title', 'values' => ['ar' => 'معلومات التواصل', 'en' => 'Contact information', 'fr' => 'Coordonnées']],
            ['group' => 'settings.general', 'key' => 'support_email', 'values' => ['ar' => 'البريد الإلكتروني للدعم', 'en' => 'Support email', 'fr' => 'E-mail de support']],
            ['group' => 'settings.general', 'key' => 'support_phone', 'values' => ['ar' => 'هاتف الدعم', 'en' => 'Support phone', 'fr' => 'Téléphone de support']],
            ['group' => 'settings.general', 'key' => 'save', 'values' => ['ar' => 'حفظ الإعدادات', 'en' => 'Save settings', 'fr' => 'Enregistrer les paramètres']],
            ['group' => 'settings.general', 'key' => 'readonly', 'values' => ['ar' => 'لديك صلاحية العرض فقط — لا يمكنك تعديل هذه الإعدادات.', 'en' => 'View-only access — you cannot edit these settings.', 'fr' => 'Accès lecture seule — modification impossible.']],
            ['group' => 'messages', 'key' => 'general.saved', 'values' => ['ar' => 'تم حفظ الإعدادات العامة بنجاح.', 'en' => 'General settings saved successfully.', 'fr' => 'Paramètres généraux enregistrés avec succès.']],
            ['group' => 'permissions', 'key' => 'settings_general_view', 'values' => ['ar' => 'عرض الإعدادات العامة', 'en' => 'View general settings', 'fr' => 'Voir les paramètres généraux']],
            ['group' => 'permissions', 'key' => 'settings_general_edit', 'values' => ['ar' => 'تعديل الإعدادات العامة', 'en' => 'Edit general settings', 'fr' => 'Modifier les paramètres généraux']],
            ['group' => 'permissions.groups', 'key' => 'general_settings', 'values' => ['ar' => 'الإعدادات العامة', 'en' => 'General settings', 'fr' => 'Paramètres généraux']],
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
