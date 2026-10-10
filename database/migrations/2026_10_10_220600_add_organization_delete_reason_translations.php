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
        ['group' => 'messages', 'key' => 'organization.cannot_delete_because', 'values' => [
            'ar' => 'لا يمكن حذف هذه الوحدة لأن :reasons.',
            'en' => 'This unit cannot be deleted because :reasons.',
            'fr' => 'Impossible de supprimer cette unité car :reasons.',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.children', 'values' => [
            'ar' => 'تحتها :count وحدة تنظيمية',
            'en' => 'it has :count sub-units',
            'fr' => 'elle a :count sous-unités',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.folders', 'values' => [
            'ar' => 'عليها :count مجلد',
            'en' => 'it has :count folders',
            'fr' => 'elle a :count dossiers',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.transactions', 'values' => [
            'ar' => 'عليها :count معاملة',
            'en' => 'it has :count transactions',
            'fr' => 'elle a :count transactions',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.employees', 'values' => [
            'ar' => 'عليها :count موظف',
            'en' => 'it has :count employees',
            'fr' => 'elle a :count employés',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.documents', 'values' => [
            'ar' => 'عليها :count وثيقة',
            'en' => 'it has :count documents',
            'fr' => 'elle a :count documents',
        ]],
        ['group' => 'messages', 'key' => 'organization.cannot_delete_reason.separator', 'values' => [
            'ar' => '، و',
            'en' => ' and ',
            'fr' => ' et ',
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
