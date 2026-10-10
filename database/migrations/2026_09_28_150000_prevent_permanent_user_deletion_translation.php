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
        ['group' => 'messages', 'key' => 'user.cannot_permanently_delete', 'values' => [
            'ar' => 'لا يمكن حذف المستخدم نهائياً من المنظومة. يمكنك تعديل بياناته بدلاً من حذفه.',
            'en' => 'Users cannot be permanently deleted from the system. You can edit their data instead.',
            'fr' => 'Un utilisateur ne peut pas être supprimé définitivement du système. Vous pouvez modifier ses données à la place.',
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
