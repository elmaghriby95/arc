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
        ['group' => 'settings.users', 'key' => 'permissions_title', 'values' => [
            'ar' => 'الصلاحيات',
            'en' => 'Permissions',
            'fr' => 'Permissions',
        ]],
        ['group' => 'settings.users', 'key' => 'permissions_hint', 'values' => [
            'ar' => 'هذه صلاحيات هذا المستخدم وحده. فعّل خانة لإضافة صلاحية له، أو ألغِ التحديد لإيقافها عنه. بقية المستخدمين على نفس الدور لا يتأثرون.',
            'en' => 'These permissions apply to this user only. Check a box to grant a permission, or clear it to turn that permission off. Other users on the same role are not affected.',
            'fr' => 'Ces permissions concernent uniquement cet utilisateur. Cochez une case pour l\'ajouter, ou décochez-la pour la retirer. Les autres utilisateurs du même rôle ne sont pas affectés.',
        ]],
        ['group' => 'settings.users', 'key' => 'permissions_role_hint', 'values' => [
            'ar' => 'عند تغيير الدور تُحدَّث الخانات حسب صلاحيات الدور الجديد، ويمكنك بعدها التعديل قبل الحفظ.',
            'en' => 'Changing the role updates the boxes to match that role. You can still adjust them before saving.',
            'fr' => 'Changer le rôle met à jour les cases selon ce rôle. Vous pouvez encore les ajuster avant d\'enregistrer.',
        ]],
        ['group' => 'settings.users', 'key' => 'permissions_search', 'values' => [
            'ar' => 'بحث في الصلاحيات',
            'en' => 'Search permissions',
            'fr' => 'Rechercher une permission',
        ]],
        ['group' => 'settings.users', 'key' => 'permission_added', 'values' => [
            'ar' => 'مضافة',
            'en' => 'Added',
            'fr' => 'Ajoutée',
        ]],
        ['group' => 'settings.users', 'key' => 'permission_revoked', 'values' => [
            'ar' => 'موقوفة',
            'en' => 'Off',
            'fr' => 'Retirée',
        ]],
        ['group' => 'validation', 'key' => 'user.cannot_remove_own_user_edit', 'values' => [
            'ar' => 'لا يمكنك إيقاف صلاحية تعديل المستخدمين عن حسابك.',
            'en' => 'You cannot turn off user editing on your own account.',
            'fr' => 'Vous ne pouvez pas retirer la modification des utilisateurs de votre propre compte.',
        ]],
        ['group' => 'validation', 'key' => 'user.permission_invalid', 'values' => [
            'ar' => 'إحدى الصلاحيات المحددة غير معروفة.',
            'en' => 'One of the selected permissions is unknown.',
            'fr' => 'Une des permissions sélectionnées est inconnue.',
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
