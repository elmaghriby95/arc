<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<array{group: string, key: string, values: array<string, string>}> */
    private array $entries = [
        ['group' => 'auth', 'key' => 'logout', 'values' => [
            'ar' => 'تسجيل الخروج',
            'en' => 'Log out',
            'fr' => 'Déconnexion',
        ]],
        ['group' => 'auth', 'key' => 'current_password', 'values' => [
            'ar' => 'كلمة المرور الحالية',
            'en' => 'Current password',
            'fr' => 'Mot de passe actuel',
        ]],
        ['group' => 'auth', 'key' => 'new_password', 'values' => [
            'ar' => 'كلمة المرور الجديدة',
            'en' => 'New password',
            'fr' => 'Nouveau mot de passe',
        ]],
        ['group' => 'auth', 'key' => 'password_change_title', 'values' => [
            'ar' => 'تعيين كلمة مرور جديدة',
            'en' => 'Set a new password',
            'fr' => 'Définir un nouveau mot de passe',
        ]],
        ['group' => 'auth', 'key' => 'password_change_first', 'values' => [
            'ar' => 'كلمة المرور التي استلمتها مؤقتة. عيّن كلمة مرور خاصة بك للمتابعة، ويجب أن تختلف عن الحالية.',
            'en' => 'The password you were given is temporary. Choose your own password to continue, and it must be different from the current one.',
            'fr' => 'Le mot de passe reçu est temporaire. Choisissez le vôtre pour continuer, et il doit être différent de l\'actuel.',
        ]],
        ['group' => 'auth', 'key' => 'password_change_expired', 'values' => [
            'ar' => 'مرّ 30 يوماً على كلمة مرورك. عيّن كلمة مرور جديدة مختلفة عن الحالية للمتابعة.',
            'en' => 'Your password is 30 days old. Choose a new password that is different from the current one to continue.',
            'fr' => 'Votre mot de passe a 30 jours. Choisissez-en un nouveau, différent de l\'actuel, pour continuer.',
        ]],
        ['group' => 'auth', 'key' => 'password_change_submit', 'values' => [
            'ar' => 'حفظ كلمة المرور',
            'en' => 'Save password',
            'fr' => 'Enregistrer le mot de passe',
        ]],
        ['group' => 'auth', 'key' => 'password_change_required', 'values' => [
            'ar' => 'يجب تغيير كلمة المرور قبل متابعة استخدام النظام.',
            'en' => 'You must change your password before continuing.',
            'fr' => 'Vous devez changer votre mot de passe avant de continuer.',
        ]],
        ['group' => 'auth', 'key' => 'password_reused', 'values' => [
            'ar' => 'لا يمكن تكرار كلمة المرور السابقة. اختر كلمة مرور مختلفة.',
            'en' => 'You cannot reuse the previous password. Choose a different one.',
            'fr' => 'Vous ne pouvez pas réutiliser le mot de passe précédent. Choisissez-en un autre.',
        ]],
        ['group' => 'auth', 'key' => 'password_changed', 'values' => [
            'ar' => 'تم تعيين كلمة المرور الجديدة.',
            'en' => 'Your new password has been saved.',
            'fr' => 'Votre nouveau mot de passe a été enregistré.',
        ]],
        ['group' => 'settings.users', 'key' => 'temporary_password_hint', 'values' => [
            'ar' => 'سيُطلب من الموظف تعيين كلمة مرور جديدة عند أول تسجيل دخول، ثم كل 30 يوماً، ولا يمكنه تكرار كلمة المرور السابقة.',
            'en' => 'The employee must set a new password on first login, then every 30 days, and cannot reuse the previous password.',
            'fr' => 'L\'employé doit définir un nouveau mot de passe à la première connexion, puis tous les 30 jours, sans réutiliser le précédent.',
        ]],
        ['group' => 'settings.users', 'key' => 'password_force_change_hint', 'values' => [
            'ar' => 'إذا حفظت كلمة مرور جديدة، سيُطلب من الموظف تغييرها عند تسجيل الدخول التالي.',
            'en' => 'Saving a new password requires the employee to replace it at the next login.',
            'fr' => 'Enregistrer un nouveau mot de passe oblige l\'employé à le remplacer à la prochaine connexion.',
        ]],
        ['group' => 'profile', 'key' => 'password_policy', 'values' => [
            'ar' => 'يجب أن تختلف عن كلمة المرور الحالية، وتُجدَّد كل 30 يوماً.',
            'en' => 'The new password must be different from the current one, and it must be changed every 30 days.',
            'fr' => 'Le nouveau mot de passe doit être différent de l\'actuel, et il doit être changé tous les 30 jours.',
        ]],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

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

        TranslationCache::forgetAll();
    }

    public function down(): void
    {
        if (! Schema::hasTable('translation_keys')) {
            return;
        }

        foreach ($this->entries as $entry) {
            TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->delete();
        }

        if (Schema::hasTable('languages')) {
            TranslationCache::forgetAll();
        }
    }
};
