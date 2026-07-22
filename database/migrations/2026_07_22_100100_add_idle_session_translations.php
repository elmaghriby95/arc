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
        ['group' => 'settings.general', 'key' => 'session_title', 'values' => [
            'ar' => 'أمان الجلسة',
            'en' => 'Session security',
            'fr' => 'Sécurité de session',
        ]],
        ['group' => 'settings.general', 'key' => 'session_desc', 'values' => [
            'ar' => 'تسجيل الخروج التلقائي عند توقف استخدام المنظومة',
            'en' => 'Automatic logout when the system is unused',
            'fr' => 'Déconnexion automatique en cas d\'inactivité',
        ]],
        ['group' => 'settings.general', 'key' => 'idle_timeout_minutes', 'values' => [
            'ar' => 'مهلة الخمول (بالدقائق)',
            'en' => 'Idle timeout (minutes)',
            'fr' => 'Délai d\'inactivité (minutes)',
        ]],
        ['group' => 'settings.general', 'key' => 'idle_timeout_minutes_desc', 'values' => [
            'ar' => 'إذا لم يتفاعل المستخدم مع المنظومة خلال هذه المدة يتم تسجيل خروجه تلقائياً. القيمة 0 تعطّل الميزة. الحد الأقصى: 480 دقيقة (8 ساعات). الافتراضي: دقيقة واحدة.',
            'en' => 'Users are logged out automatically after this many minutes of inactivity. Use 0 to disable. Maximum: 480 minutes (8 hours). Default: 1 minute.',
            'fr' => 'Les utilisateurs sont déconnectés automatiquement après ce nombre de minutes d\'inactivité. 0 pour désactiver. Maximum : 480 minutes (8 heures). Par défaut : 1 minute.',
        ]],
        ['group' => 'auth', 'key' => 'idle_logged_out', 'values' => [
            'ar' => 'تم تسجيل خروجك تلقائياً بسبب عدم النشاط.',
            'en' => 'You were logged out automatically due to inactivity.',
            'fr' => 'Vous avez été déconnecté automatiquement en raison d\'inactivité.',
        ]],
        ['group' => 'auth', 'key' => 'idle_warning_title', 'values' => [
            'ar' => 'انتهاء الجلسة قريباً',
            'en' => 'Session expiring soon',
            'fr' => 'Session bientôt expirée',
        ]],
        ['group' => 'auth', 'key' => 'idle_warning_body', 'values' => [
            'ar' => 'سيتم تسجيل خروجك تلقائياً بسبب عدم النشاط خلال ثوانٍ.',
            'en' => 'You will be logged out automatically due to inactivity in a few seconds.',
            'fr' => 'Vous serez déconnecté automatiquement pour inactivité dans quelques secondes.',
        ]],
        ['group' => 'auth', 'key' => 'idle_stay_signed_in', 'values' => [
            'ar' => 'البقاء متصلاً',
            'en' => 'Stay signed in',
            'fr' => 'Rester connecté',
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
