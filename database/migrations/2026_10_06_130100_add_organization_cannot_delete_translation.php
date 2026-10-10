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
        ['group' => 'messages', 'key' => 'organization.cannot_delete_in_use', 'values' => [
            'ar' => 'لا يمكن حذف هذه الوحدة لأن تحتها وحدات أخرى، أو عليها مجلد أو معاملة أو موظف أو وثيقة. الحذف متاح فقط للوحدة الفارغة حتى لا يتأثر أي شيء آخر.',
            'en' => 'This unit cannot be deleted while it has sub-units, a folder, a transaction, an employee, or a document. Only an empty unit can be deleted, so nothing else is changed.',
            'fr' => 'Impossible de supprimer cette unité tant qu\'elle a des sous-unités, un dossier, une transaction, un employé ou un document. Seule une unité vide peut être supprimée, sans affecter le reste.',
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
