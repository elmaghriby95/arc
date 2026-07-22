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
        'documents.print',
    ];

    /** @var list<array{group: string, key: string, values: array<string, string>}> */
    private array $translations = [
        ['group' => 'permissions', 'key' => 'documents_print', 'values' => [
            'ar' => 'طباعة وثيقة',
            'en' => 'Print document',
            'fr' => 'Imprimer un document',
        ]],
    ];

    public function up(): void
    {
        $printPermission = Permission::DocumentsPrint->value;

        foreach (Role::all() as $role) {
            $current = $role->permissions ?? [];

            $shouldGrant = $role->slug === 'admin'
                || in_array(Permission::DocumentsView->value, $current, true)
                || in_array(Permission::DocumentsDownload->value, $current, true)
                || in_array(Permission::LendingRequestsReview->value, $current, true)
                || in_array(Permission::LendingRequestsHandover->value, $current, true);

            if (! $shouldGrant) {
                continue;
            }

            $role->update([
                'permissions' => array_values(array_unique(array_merge($current, [$printPermission]))),
            ]);
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
