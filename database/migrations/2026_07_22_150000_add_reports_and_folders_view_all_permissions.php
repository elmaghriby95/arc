<?php

use App\Enums\Permission;
use App\Models\Language;
use App\Models\Role;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Services\TranslationCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $newPermissions = [
        'reports.view-all',
        'settings.folders.view-all',
    ];

    /** @var list<array{group: string, key: string, values: array<string, string>}> */
    private array $translations = [
        ['group' => 'permissions', 'key' => 'reports_view-all', 'values' => [
            'ar' => 'عرض تقارير كل الوحدات التنظيمية',
            'en' => 'View reports for all organizational units',
            'fr' => 'Voir les rapports de toutes les unités organisationnelles',
        ]],
        ['group' => 'permissions', 'key' => 'settings_folders_view-all', 'values' => [
            'ar' => 'عرض كل المجلدات',
            'en' => 'View all folders',
            'fr' => 'Voir tous les dossiers',
        ]],
    ];

    public function up(): void
    {
        $admin = Role::query()->where('slug', 'admin')->first();

        if ($admin) {
            $permissions = array_values(array_unique(array_merge(
                $admin->permissions ?? [],
                $this->newPermissions,
                [
                    Permission::ReportsViewAll->value,
                    Permission::SettingsFoldersViewAll->value,
                ],
            )));
            $admin->update(['permissions' => $permissions]);
        }

        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        foreach ($this->translations as $entry) {
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
        foreach (Role::all() as $role) {
            $permissions = array_values(array_filter(
                $role->permissions ?? [],
                fn (string $permission) => ! in_array($permission, $this->newPermissions, true)
            ));

            $role->update(['permissions' => $permissions]);
        }

        if (! Schema::hasTable('translation_keys')) {
            return;
        }

        foreach ($this->translations as $entry) {
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
