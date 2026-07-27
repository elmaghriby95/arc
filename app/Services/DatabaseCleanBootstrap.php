<?php

namespace App\Services;

use App\Enums\Permission;
use App\Models\Language;
use App\Models\Role;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * يفعّل صلاحية وترجمات تنظيف قاعدة البيانات بدون الحاجة لتشغيل artisan migrate.
 */
class DatabaseCleanBootstrap
{
    private const CACHE_KEY = 'app.bootstrap.database_clean.v1';

    public static function ensure(): void
    {
        if (Cache::get(self::CACHE_KEY) === true) {
            return;
        }

        if (! Schema::hasTable('roles')) {
            return;
        }

        self::ensureAdminPermission();
        self::seedTranslations();

        Cache::forever(self::CACHE_KEY, true);
    }

    private static function ensureAdminPermission(): void
    {
        $admin = Role::query()->where('slug', Role::SUPER_ADMIN_SLUG)->first();

        if ($admin) {
            // يفرض كل الصلاحيات بما فيها settings.database-clean
            $admin->save();
        }

        $blocked = Permission::SettingsDatabaseClean->value;

        foreach (Role::query()->where('slug', '!=', Role::SUPER_ADMIN_SLUG)->cursor() as $role) {
            $current = $role->permissions ?? [];

            if (! in_array($blocked, $current, true)) {
                continue;
            }

            $role->update([
                'permissions' => array_values(array_filter(
                    $current,
                    fn (string $permission) => $permission !== $blocked
                )),
            ]);
        }
    }

    private static function seedTranslations(): void
    {
        if (! Schema::hasTable('translation_keys')
            || ! Schema::hasTable('translations')
            || ! Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');

        if ($languages->isEmpty()) {
            return;
        }

        foreach (self::translations() as $entry) {
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

    /** @return list<array{group: string, key: string, values: array<string, string>}> */
    private static function translations(): array
    {
        return [
            ['group' => 'permissions', 'key' => 'settings_database-clean', 'values' => [
                'ar' => 'تنظيف قاعدة البيانات (سوبر أدمن فقط)',
                'en' => 'Clean database (super admin only)',
                'fr' => 'Nettoyer la base (super admin uniquement)',
            ]],
            ['group' => 'permissions.groups', 'key' => 'database_clean', 'values' => [
                'ar' => 'تنظيف قاعدة البيانات',
                'en' => 'Database cleanup',
                'fr' => 'Nettoyage de la base',
            ]],
            ['group' => 'settings', 'key' => 'database_clean_title', 'values' => [
                'ar' => 'تنظيف قاعدة البيانات',
                'en' => 'Database Cleanup',
                'fr' => 'Nettoyage de la base',
            ]],
            ['group' => 'settings', 'key' => 'database_clean_desc', 'values' => [
                'ar' => 'حذف المجلدات والمعاملات والوثائق والهيكل التنظيمي وبيانات الأرشيف التشغيلية',
                'en' => 'Delete folders, transactions, documents, organization structure and operational archive data',
                'fr' => 'Supprimer dossiers, transactions, documents, structure organisationnelle et données opérationnelles',
            ]],
            ['group' => 'settings', 'key' => 'database_clean_badge', 'values' => [
                'ar' => 'سوبر أدمن فقط',
                'en' => 'Super admin only',
                'fr' => 'Super admin uniquement',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'title', 'values' => [
                'ar' => 'تنظيف قاعدة البيانات',
                'en' => 'Database Cleanup',
                'fr' => 'Nettoyage de la base',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'subtitle', 'values' => [
                'ar' => 'إجراء خطير متاح لمدير النظام فقط — لا يمكن التراجع عنه',
                'en' => 'Dangerous action available to system admin only — irreversible',
                'fr' => 'Action dangereuse réservée au super admin — irréversible',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'warning', 'values' => [
                'ar' => 'سيُحذف نهائياً: المجلدات، المعاملات ومرفقاتها، الوثائق وإصداراتها، أنواع المستندات والمعاملات، الهيكل التنظيمي (الوحدات الإدارية)، طلبات الإعارة، سجلات التدقيق والإشعارات، وملفات الأرشيف المخزّنة.',
                'en' => 'Permanently deletes: folders, transactions and attachments, documents and versions, document/transaction types, organization units, lending requests, audit logs, notifications, and stored archive files.',
                'fr' => 'Suppression définitive des données opérationnelles d\'archivage.',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'kept', 'values' => [
                'ar' => 'لن يُمس: الترجمات واللغات، الإعدادات العامة (الاسم والشعار)، إعدادات الرقم الإشاري ورمز QR والعلامة المائية، حالات سير العمل، الأدوار، وحسابات مدير النظام وبقية المستخدمين (مع فصلهم عن الوحدات المحذوفة).',
                'en' => 'Preserved: translations, languages, general settings, reference/QR/watermark settings, workflow statuses, roles, and users. Super admin remains unchanged.',
                'fr' => 'Conservé : traductions, paramètres généraux, rôles et utilisateurs. Le super admin reste intact.',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'preview_title', 'values' => [
                'ar' => 'ملخص ما سيتم حذفه',
                'en' => 'Preview of data to delete',
                'fr' => 'Aperçu des données à supprimer',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_transactions', 'values' => [
                'ar' => 'معاملات', 'en' => 'Transactions', 'fr' => 'Transactions',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_attachments', 'values' => [
                'ar' => 'مرفقات', 'en' => 'Attachments', 'fr' => 'Pièces jointes',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_documents', 'values' => [
                'ar' => 'وثائق', 'en' => 'Documents', 'fr' => 'Documents',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_folders', 'values' => [
                'ar' => 'مجلدات', 'en' => 'Folders', 'fr' => 'Dossiers',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_departments', 'values' => [
                'ar' => 'وحدات تنظيمية', 'en' => 'Org units', 'fr' => 'Unités org.',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_document_types', 'values' => [
                'ar' => 'أنواع مستندات', 'en' => 'Document types', 'fr' => 'Types de documents',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_transaction_types', 'values' => [
                'ar' => 'أنواع معاملات', 'en' => 'Transaction types', 'fr' => 'Types de transactions',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_lending', 'values' => [
                'ar' => 'طلبات إعارة', 'en' => 'Lending requests', 'fr' => 'Demandes de prêt',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_audits', 'values' => [
                'ar' => 'سجلات تدقيق', 'en' => 'Audit logs', 'fr' => 'Journaux d\'audit',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'count_notifications', 'values' => [
                'ar' => 'إشعارات', 'en' => 'Notifications', 'fr' => 'Notifications',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'confirm_title', 'values' => [
                'ar' => 'تأكيد التنظيف', 'en' => 'Confirm cleanup', 'fr' => 'Confirmer le nettoyage',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'confirmation_label', 'values' => [
                'ar' => 'اكتب «:phrase» للتأكيد',
                'en' => 'Type «:phrase» to confirm',
                'fr' => 'Tapez «:phrase» pour confirmer',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'confirmation_hint', 'values' => [
                'ar' => 'يجب كتابة العبارة حرفياً: :phrase',
                'en' => 'Type the phrase exactly: :phrase',
                'fr' => 'Saisissez exactement : :phrase',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'password_label', 'values' => [
                'ar' => 'كلمة مرور حسابك',
                'en' => 'Your account password',
                'fr' => 'Mot de passe du compte',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'submit', 'values' => [
                'ar' => 'تنفيذ التنظيف النهائي',
                'en' => 'Run final cleanup',
                'fr' => 'Lancer le nettoyage final',
            ]],
            ['group' => 'settings.database_clean', 'key' => 'js_confirm', 'values' => [
                'ar' => 'هل أنت متأكد؟ لا يمكن التراجع عن هذا الإجراء.',
                'en' => 'Are you sure? This action cannot be undone.',
                'fr' => 'Êtes-vous sûr ? Cette action est irréversible.',
            ]],
            ['group' => 'messages', 'key' => 'database_clean.completed', 'values' => [
                'ar' => 'تم تنظيف البيانات التشغيلية بنجاح. بقيت الترجمات والإعدادات العامة وحسابات مدير النظام كما هي.',
                'en' => 'Operational data cleaned successfully. Translations, general settings and super admin accounts were preserved.',
                'fr' => 'Données opérationnelles nettoyées. Traductions, paramètres généraux et comptes super admin conservés.',
            ]],
            ['group' => 'validation', 'key' => 'database_clean.confirmation_required', 'values' => [
                'ar' => 'يجب كتابة عبارة التأكيد.',
                'en' => 'Confirmation phrase is required.',
                'fr' => 'La phrase de confirmation est requise.',
            ]],
            ['group' => 'validation', 'key' => 'database_clean.confirmation_mismatch', 'values' => [
                'ar' => 'عبارة التأكيد غير مطابقة.',
                'en' => 'Confirmation phrase does not match.',
                'fr' => 'La phrase de confirmation ne correspond pas.',
            ]],
            ['group' => 'validation', 'key' => 'database_clean.password_required', 'values' => [
                'ar' => 'كلمة المرور مطلوبة لتأكيد العملية.',
                'en' => 'Password is required to confirm.',
                'fr' => 'Le mot de passe est requis pour confirmer.',
            ]],
            ['group' => 'validation', 'key' => 'database_clean.password_incorrect', 'values' => [
                'ar' => 'كلمة المرور غير صحيحة.',
                'en' => 'Incorrect password.',
                'fr' => 'Mot de passe incorrect.',
            ]],
        ];
    }
}
