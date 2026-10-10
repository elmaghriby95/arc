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
        ['group' => 'nav', 'key' => 'tagline', 'values' => [
            'ar' => 'منصة السجلات والأرشفة المؤسسية',
            'en' => 'Institutional Records & Archive Platform',
            'fr' => 'Plateforme des dossiers et archives institutionnels',
        ]],
        ['group' => 'nav', 'key' => 'search_documents', 'values' => [
            'ar' => 'البحث في الوثائق...',
            'en' => 'Search documents...',
            'fr' => 'Rechercher dans les documents...',
        ]],
        ['group' => 'auth', 'key' => 'brand_subtitle', 'values' => [
            'ar' => 'منصة السجلات والأرشفة المؤسسية',
            'en' => 'Institutional Records & Archive Platform',
            'fr' => 'Plateforme des dossiers et archives institutionnels',
        ]],
        ['group' => 'auth', 'key' => 'username', 'values' => [
            'ar' => 'اسم المستخدم',
            'en' => 'Username',
            'fr' => 'Nom d\'utilisateur',
        ]],
    ];

    public function up(): void
    {
        $this->write($this->entries);
    }

    public function down(): void
    {
        if (! Schema::hasTable('translation_keys')) {
            return;
        }

        foreach (['search_documents', 'username'] as $key) {
            TranslationKey::query()->where('key', $key)->whereIn('group', ['nav', 'auth'])->delete();
        }

        $this->write([
            ['group' => 'nav', 'key' => 'tagline', 'values' => [
                'ar' => 'إدارة الوثائق والأرشفة',
                'en' => 'Document & Archive Management',
                'fr' => 'Gestion des documents et archives',
            ]],
            ['group' => 'auth', 'key' => 'brand_subtitle', 'values' => [
                'ar' => 'منصة متكاملة لإدارة وأرشفة الوثائق الإلكترونية بأمان وكفاءة',
                'en' => 'Integrated platform for secure document archiving',
                'fr' => 'Plateforme intégrée pour l\'archivage sécurisé',
            ]],
        ]);
    }

    /** @param  list<array{group: string, key: string, values: array<string, string>}>  $entries */
    private function write(array $entries): void
    {
        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        foreach ($entries as $entry) {
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
};
