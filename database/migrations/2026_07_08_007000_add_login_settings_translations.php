<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** @var list<array{group: string, key: string, values: array<string, string>}> */
    private array $entries = [
        ['group' => 'settings.general', 'key' => 'login_texts_title', 'values' => [
            'ar' => 'نصوص صفحة تسجيل الدخول',
            'en' => 'Login page texts',
            'fr' => 'Textes de la page de connexion',
        ]],
        ['group' => 'settings.general', 'key' => 'login_texts_desc', 'values' => [
            'ar' => 'جميع النصوص الظاهرة في صفحة تسجيل الدخول — لكل لغة على حدة',
            'en' => 'All texts shown on the login page — per language',
            'fr' => 'Tous les textes affichés sur la page de connexion — par langue',
        ]],
        ['group' => 'settings.general', 'key' => 'login_welcome_back', 'values' => [
            'ar' => 'عنوان الترحيب',
            'en' => 'Welcome heading',
            'fr' => 'Titre de bienvenue',
        ]],
        ['group' => 'settings.general', 'key' => 'login_desc', 'values' => [
            'ar' => 'وصف صفحة الدخول',
            'en' => 'Login page description',
            'fr' => 'Description de la page de connexion',
        ]],
        ['group' => 'settings.general', 'key' => 'login_brand_subtitle', 'values' => [
            'ar' => 'وصف المنصة',
            'en' => 'Platform description',
            'fr' => 'Description de la plateforme',
        ]],
        ['group' => 'settings.general', 'key' => 'login_feature_1', 'values' => [
            'ar' => 'الميزة الأولى',
            'en' => 'Feature 1',
            'fr' => 'Fonctionnalité 1',
        ]],
        ['group' => 'settings.general', 'key' => 'login_feature_2', 'values' => [
            'ar' => 'الميزة الثانية',
            'en' => 'Feature 2',
            'fr' => 'Fonctionnalité 2',
        ]],
        ['group' => 'settings.general', 'key' => 'login_feature_3', 'values' => [
            'ar' => 'الميزة الثالثة',
            'en' => 'Feature 3',
            'fr' => 'Fonctionnalité 3',
        ]],
        ['group' => 'settings.general', 'key' => 'login_email', 'values' => [
            'ar' => 'تسمية البريد الإلكتروني',
            'en' => 'Email label',
            'fr' => 'Libellé e-mail',
        ]],
        ['group' => 'settings.general', 'key' => 'login_password', 'values' => [
            'ar' => 'تسمية كلمة المرور',
            'en' => 'Password label',
            'fr' => 'Libellé mot de passe',
        ]],
        ['group' => 'settings.general', 'key' => 'login_email_placeholder', 'values' => [
            'ar' => 'نص توضيحي للبريد الإلكتروني',
            'en' => 'Email placeholder',
            'fr' => 'Texte indicatif e-mail',
        ]],
        ['group' => 'settings.general', 'key' => 'login_password_placeholder', 'values' => [
            'ar' => 'نص توضيحي لكلمة المرور',
            'en' => 'Password placeholder',
            'fr' => 'Texte indicatif mot de passe',
        ]],
        ['group' => 'settings.general', 'key' => 'login_remember_me', 'values' => [
            'ar' => 'تذكرني',
            'en' => 'Remember me',
            'fr' => 'Se souvenir de moi',
        ]],
        ['group' => 'settings.general', 'key' => 'login_forgot_password', 'values' => [
            'ar' => 'نسيت كلمة المرور',
            'en' => 'Forgot password',
            'fr' => 'Mot de passe oublié',
        ]],
        ['group' => 'settings.general', 'key' => 'login_button', 'values' => [
            'ar' => 'زر تسجيل الدخول',
            'en' => 'Login button',
            'fr' => 'Bouton de connexion',
        ]],
        ['group' => 'settings.general', 'key' => 'login_no_account', 'values' => [
            'ar' => 'لا تملك حساباً؟',
            'en' => 'No account text',
            'fr' => 'Pas de compte ?',
        ]],
        ['group' => 'settings.general', 'key' => 'login_register', 'values' => [
            'ar' => 'رابط إنشاء حساب',
            'en' => 'Register link',
            'fr' => 'Lien d\'inscription',
        ]],
        ['group' => 'settings.general', 'key' => 'login_page_title', 'values' => [
            'ar' => 'عنوان الصفحة (تبويب المتصفح)',
            'en' => 'Page title (browser tab)',
            'fr' => 'Titre de la page (onglet)',
        ]],
        ['group' => 'settings.general', 'key' => 'login_copyright', 'values' => [
            'ar' => 'نص حقوق النشر',
            'en' => 'Copyright text',
            'fr' => 'Texte de copyright',
        ]],
    ];

    public function up(): void
    {
        $languages = Language::query()->pluck('id', 'code');

        foreach ($this->entries as $entry) {
            $key = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
            );

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $key->id, 'language_id' => $languages[$code]],
                    ['value' => $value],
                );
            }
        }

        TranslationKey::query()
            ->where('group', 'settings.general')
            ->where('key', 'info')
            ->each(function (TranslationKey $key): void {
                Translation::query()
                    ->where('translation_key_id', $key->id)
                    ->whereHas('language', fn ($q) => $q->where('code', 'ar'))
                    ->update(['value' => 'تُطبَّق هذه الإعدادات على شريط التنقل وصفحة تسجيل الدخول وعناوين الصفحات. يمكن تعديل جميع نصوص صفحة الدخول من قسم «نصوص صفحة تسجيل الدخول» أدناه.']);

                Translation::query()
                    ->where('translation_key_id', $key->id)
                    ->whereHas('language', fn ($q) => $q->where('code', 'en'))
                    ->update(['value' => 'These settings apply to the navbar, login page and page titles. All login page texts can be edited in the «Login page texts» section below.']);

                Translation::query()
                    ->where('translation_key_id', $key->id)
                    ->whereHas('language', fn ($q) => $q->where('code', 'fr'))
                    ->update(['value' => 'Ces paramètres s\'appliquent à la barre de navigation, la page de connexion et les titres. Tous les textes de la page de connexion se modifient dans la section « Textes de la page de connexion » ci-dessous.']);
            });
    }

    public function down(): void
    {
        foreach ($this->entries as $entry) {
            TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->delete();
        }
    }
};
