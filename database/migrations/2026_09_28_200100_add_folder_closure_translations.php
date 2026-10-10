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
        ['group' => 'messages', 'key' => 'folder.closed', 'values' => [
            'ar' => 'تم إغلاق المجلد. لن يظهر عند إنشاء المعاملات.',
            'en' => 'Folder closed. It will not appear when creating transactions.',
            'fr' => 'Dossier fermé. Il n\'apparaîtra plus lors de la création des transactions.',
        ]],
        ['group' => 'messages', 'key' => 'folder.reopened', 'values' => [
            'ar' => 'تم إعادة فتح المجلد وسيظهر عند إنشاء المعاملات.',
            'en' => 'Folder reopened. It will appear again when creating transactions.',
            'fr' => 'Dossier rouvert. Il réapparaîtra lors de la création des transactions.',
        ]],
        ['group' => 'messages', 'key' => 'folder.closed_unavailable', 'values' => [
            'ar' => 'هذا المجلد مغلق ولا يمكن إنشاء معاملة فيه.',
            'en' => 'This folder is closed and cannot receive a new transaction.',
            'fr' => 'Ce dossier est fermé et ne peut pas recevoir une nouvelle transaction.',
        ]],
        ['group' => 'settings.folders', 'key' => 'close', 'values' => [
            'ar' => 'إغلاق المجلد',
            'en' => 'Close folder',
            'fr' => 'Fermer le dossier',
        ]],
        ['group' => 'settings.folders', 'key' => 'reopen', 'values' => [
            'ar' => 'إعادة فتح المجلد',
            'en' => 'Reopen folder',
            'fr' => 'Rouvrir le dossier',
        ]],
        ['group' => 'settings.folders', 'key' => 'close_short', 'values' => [
            'ar' => 'إغلاق',
            'en' => 'Close',
            'fr' => 'Fermer',
        ]],
        ['group' => 'settings.folders', 'key' => 'reopen_short', 'values' => [
            'ar' => 'إعادة فتح',
            'en' => 'Reopen',
            'fr' => 'Rouvrir',
        ]],
        ['group' => 'settings.folders', 'key' => 'closed_badge', 'values' => [
            'ar' => 'مغلق',
            'en' => 'Closed',
            'fr' => 'Fermé',
        ]],
        ['group' => 'settings.folders', 'key' => 'closed_label', 'values' => [
            'ar' => 'مغلق — لا يظهر في إنشاء المعاملات',
            'en' => 'Closed — hidden when creating transactions',
            'fr' => 'Fermé — masqué lors de la création des transactions',
        ]],
        ['group' => 'settings.folders', 'key' => 'closed_hint', 'values' => [
            'ar' => 'المجلد المغلق يختفي بالكامل من شاشة إنشاء معاملة جديدة. المعاملات الموجودة تبقى كما هي.',
            'en' => 'A closed folder is hidden completely from new transaction creation. Existing transactions stay in place.',
            'fr' => 'Un dossier fermé disparaît complètement de la création d\'une transaction. Les transactions existantes restent en place.',
        ]],
        ['group' => 'settings.folders', 'key' => 'close_confirm', 'values' => [
            'ar' => 'لن يظهر هذا المجلد في إنشاء المعاملات بعد إغلاقه. هل تريد المتابعة؟',
            'en' => 'This folder will no longer appear when creating transactions. Continue?',
            'fr' => 'Ce dossier n\'apparaîtra plus lors de la création des transactions. Continuer ?',
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
