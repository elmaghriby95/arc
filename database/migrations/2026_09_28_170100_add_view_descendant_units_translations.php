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
        ['group' => 'settings.users', 'key' => 'view_descendant_units', 'values' => [
            'ar' => 'عرض الوحدات التابعة',
            'en' => 'View subordinate units',
            'fr' => 'Voir les unités subordonnées',
        ]],
        ['group' => 'settings.users', 'key' => 'view_descendant_units_hint', 'values' => [
            'ar' => 'عند التفعيل يرى الموظف وحدته وكل الإدارات والأقسام والمعاملات تحتها. عند الإلغاء يرى وحدته فقط.',
            'en' => 'When enabled, the employee sees their unit and every administration, department, and transaction under it. When disabled, they see only their own unit.',
            'fr' => 'Activé : l\'employé voit son unité et tout ce qui est en dessous. Désactivé : son unité seulement.',
        ]],
        ['group' => 'settings.users', 'key' => 'scope_line_1', 'values' => [
            'ar' => 'بدون الخيار يرى الموظف بيانات وحدته فقط.',
            'en' => 'Without the option, the employee sees only their own unit.',
            'fr' => 'Sans l\'option, l\'employé ne voit que son unité.',
        ]],
        ['group' => 'settings.users', 'key' => 'scope_line_2', 'values' => [
            'ar' => 'مع تفعيل «عرض الوحدات التابعة» يرى أيضاً كل الإدارات والأقسام والمعاملات تحتها.',
            'en' => 'With “View subordinate units” enabled, they also see every administration, department, and transaction below them.',
            'fr' => 'Avec l\'option activée, il voit aussi les unités et transactions en dessous.',
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

        foreach (['view_descendant_units', 'view_descendant_units_hint'] as $key) {
            TranslationKey::query()
                ->where('group', 'settings.users')
                ->where('key', $key)
                ->delete();
        }

        if (Schema::hasTable('languages')) {
            TranslationCache::forgetAll();
        }
    }
};
