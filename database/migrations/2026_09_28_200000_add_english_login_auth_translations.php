<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        if ($languages->isEmpty()) {
            return;
        }

        $valuesByLocale = [
            'ar' => require lang_path('ar/auth.php'),
            'en' => require lang_path('en/auth.php'),
            'fr' => $this->french(),
        ];

        foreach ($valuesByLocale['en'] as $key => $english) {
            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => 'auth', 'key' => $key],
            );

            foreach ($valuesByLocale as $code => $values) {
                if (! isset($languages[$code], $values[$key]) || ! filled($values[$key])) {
                    continue;
                }

                $translation = Translation::query()->firstOrNew([
                    'translation_key_id' => $translationKey->id,
                    'language_id' => $languages[$code],
                ]);

                if (filled($translation->value)) {
                    continue;
                }

                $translation->value = $values[$key];
                $translation->save();
            }
        }

        TranslationCache::forgetAll();
    }

    public function down(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        TranslationCache::forgetAll();
    }

    /** @return array<string, string> */
    private function french(): array
    {
        return [
            'login' => 'Connexion',
            'register' => 'Inscription',
            'email' => 'E-mail',
            'password' => 'Mot de passe',
            'remember_me' => 'Se souvenir de moi',
            'forgot_password' => 'Mot de passe oublié ?',
            'name' => 'Nom',
            'confirm_password' => 'Confirmer le mot de passe',
            'already_registered' => 'Déjà inscrit ?',
            'logged_in' => 'Vous êtes connecté !',
            'welcome_back' => 'Bon retour',
            'login_desc' => 'Connectez-vous pour accéder au tableau de bord',
            'no_account' => 'Pas de compte ?',
            'show_password' => 'Afficher le mot de passe',
            'login_page_title' => 'Connexion',
            'brand_subtitle' => 'Plateforme intégrée pour l\'archivage sécurisé',
            'feature_1' => 'Archivage central des documents',
            'feature_2' => 'Recherche rapide et classification',
            'feature_3' => 'Suivi des versions et journal d\'audit',
            'email_placeholder' => 'example@domain.com',
            'password_placeholder' => '••••••••',
            'forgot_password_intro' => 'Mot de passe oublié ? Indiquez votre e-mail et nous vous enverrons un lien de réinitialisation.',
            'email_password_reset_link' => 'Envoyer le lien de réinitialisation',
            'reset_password' => 'Réinitialiser le mot de passe',
            'failed' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
            'throttle' => 'Trop de tentatives de connexion. Veuillez réessayer dans :seconds secondes.',
            'idle_logged_out' => 'Vous avez été déconnecté automatiquement en raison d\'inactivité.',
            'idle_warning_title' => 'Session bientôt expirée',
            'idle_warning_body' => 'Vous serez déconnecté automatiquement pour inactivité dans quelques secondes.',
            'idle_stay_signed_in' => 'Rester connecté',
        ];
    }
};
