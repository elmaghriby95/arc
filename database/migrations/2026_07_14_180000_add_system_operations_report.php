<?php

use App\Enums\ReportType;
use App\Models\Language;
use App\Models\Role;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private function permission(): string
    {
        return ReportType::SystemOperations->permission();
    }

    /** @return list<array{group: string, key: string, values: array<string, string>}> */
    private function entries(): array
    {
        return [
            ['group' => 'reports', 'key' => 'phase_3', 'values' => [
                'ar' => 'تقارير مدير النظام',
                'en' => 'System admin reports',
                'fr' => 'Rapports administrateur système',
            ]],
            ['group' => 'reports', 'key' => 'system_operations.label', 'values' => [
                'ar' => 'عمليات المنظومة',
                'en' => 'System operations',
                'fr' => 'Opérations du système',
            ]],
            ['group' => 'reports', 'key' => 'system_operations.description', 'values' => [
                'ar' => 'سجل تتبّع شامل لكل عمليات الأرشفة والمراجعة والإجراءات عبر الإدارات والمستخدمين والمعاملات',
                'en' => 'Complete audit trail of archive, review, and actions across departments, users, and transactions',
                'fr' => 'Journal complet des opérations d\'archivage, de révision et des actions',
            ]],
            ['group' => 'reports', 'key' => 'permission.system_operations', 'values' => [
                'ar' => 'تقرير: عمليات المنظومة (مدير النظام)',
                'en' => 'Report: System operations (admin only)',
                'fr' => 'Rapport : Opérations système (admin uniquement)',
            ]],
            ['group' => 'reports', 'key' => 'ops.hero_title', 'values' => [
                'ar' => 'سجل عمليات المنظومة',
                'en' => 'System operations log',
                'fr' => 'Journal des opérations système',
            ]],
            ['group' => 'reports', 'key' => 'ops.hero_desc', 'values' => [
                'ar' => 'عرض تفصيلي لكل خطوة تمت على المعاملات مع المستخدم والتاريخ والوقت، مع فلترة حسب الهيكل التنظيمي والمجلدات.',
                'en' => 'Detailed view of every transaction step with user, date and time, filterable by organization and folders.',
                'fr' => 'Vue détaillée de chaque étape avec utilisateur, date et heure.',
            ]],
            ['group' => 'reports', 'key' => 'ops.total_events', 'values' => [
                'ar' => 'إجمالي العمليات',
                'en' => 'Total events',
                'fr' => 'Total des événements',
            ]],
            ['group' => 'reports', 'key' => 'ops.shown_events', 'values' => [
                'ar' => 'المعروض حالياً',
                'en' => 'Currently shown',
                'fr' => 'Affichés',
            ]],
            ['group' => 'reports', 'key' => 'ops.affected_transactions', 'values' => [
                'ar' => 'معاملات متأثرة',
                'en' => 'Affected transactions',
                'fr' => 'Transactions concernées',
            ]],
            ['group' => 'reports', 'key' => 'ops.last_activity', 'values' => [
                'ar' => 'آخر نشاط',
                'en' => 'Last activity',
                'fr' => 'Dernière activité',
            ]],
            ['group' => 'reports', 'key' => 'ops.by_event_type', 'values' => [
                'ar' => 'التوزيع حسب نوع العملية',
                'en' => 'Breakdown by event type',
                'fr' => 'Répartition par type',
            ]],
            ['group' => 'reports', 'key' => 'ops.timeline_title', 'values' => [
                'ar' => 'الخط الزمني للعمليات',
                'en' => 'Operations timeline',
                'fr' => 'Chronologie des opérations',
            ]],
            ['group' => 'reports', 'key' => 'ops.no_events', 'values' => [
                'ar' => 'لا توجد عمليات مطابقة للفلاتر المحددة.',
                'en' => 'No operations match the selected filters.',
                'fr' => 'Aucune opération ne correspond aux filtres.',
            ]],
            ['group' => 'reports', 'key' => 'ops.limit_note', 'values' => [
                'ar' => 'يُعرض أحدث :shown سجل من أصل نتائج أكثر. استخدم الفلاتر أو صدّر Excel/PDF لعرض نطاق أوسع (حتى :limit).',
                'en' => 'Showing the latest :shown records. Use filters or export Excel/PDF for a wider range (up to :limit).',
                'fr' => 'Affichage des :shown derniers enregistrements. Filtrez ou exportez pour élargir (jusqu\'à :limit).',
            ]],
            ['group' => 'reports', 'key' => 'ops.transaction_created', 'values' => [
                'ar' => 'إنشاء معاملة',
                'en' => 'Transaction created',
                'fr' => 'Transaction créée',
            ]],
            ['group' => 'reports', 'key' => 'ops.status_changed', 'values' => [
                'ar' => 'تغيير حالة المعاملة',
                'en' => 'Transaction status changed',
                'fr' => 'Statut de transaction modifié',
            ]],
            ['group' => 'reports', 'key' => 'ops.attachment_uploaded', 'values' => [
                'ar' => 'رفع مرفق',
                'en' => 'Attachment uploaded',
                'fr' => 'Pièce jointe téléversée',
            ]],
            ['group' => 'reports', 'key' => 'ops.document_access', 'values' => [
                'ar' => 'وصول للمستند (:action)',
                'en' => 'Document access (:action)',
                'fr' => 'Accès document (:action)',
            ]],
            ['group' => 'reports', 'key' => 'ops.lending_action', 'values' => [
                'ar' => 'إجراء إعارة',
                'en' => 'Lending action',
                'fr' => 'Action de prêt',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit_generic', 'values' => [
                'ar' => 'نشاط النظام',
                'en' => 'System activity',
                'fr' => 'Activité système',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.login', 'values' => [
                'ar' => 'تسجيل دخول',
                'en' => 'Login',
                'fr' => 'Connexion',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.logout', 'values' => [
                'ar' => 'تسجيل خروج',
                'en' => 'Logout',
                'fr' => 'Déconnexion',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.profile.updated', 'values' => [
                'ar' => 'تحديث الملف الشخصي',
                'en' => 'Profile updated',
                'fr' => 'Profil mis à jour',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.profile.avatar_updated', 'values' => [
                'ar' => 'تحديث الصورة الشخصية',
                'en' => 'Avatar updated',
                'fr' => 'Avatar mis à jour',
            ]],
            ['group' => 'reports', 'key' => 'ops.audit.password.changed', 'values' => [
                'ar' => 'تغيير كلمة المرور',
                'en' => 'Password changed',
                'fr' => 'Mot de passe modifié',
            ]],
            ['group' => 'reports', 'key' => 'filter_folder', 'values' => [
                'ar' => 'المجلد',
                'en' => 'Folder',
                'fr' => 'Dossier',
            ]],
            ['group' => 'reports', 'key' => 'filter_user', 'values' => [
                'ar' => 'المستخدم',
                'en' => 'User',
                'fr' => 'Utilisateur',
            ]],
            ['group' => 'reports', 'key' => 'filter_transaction', 'values' => [
                'ar' => 'المعاملة',
                'en' => 'Transaction',
                'fr' => 'Transaction',
            ]],
            ['group' => 'reports', 'key' => 'filter_transaction_placeholder', 'values' => [
                'ar' => 'رقم مرجعي أو عنوان...',
                'en' => 'Reference or title...',
                'fr' => 'Référence ou titre...',
            ]],
            ['group' => 'reports', 'key' => 'filter_event_type', 'values' => [
                'ar' => 'نوع العملية',
                'en' => 'Event type',
                'fr' => 'Type d\'événement',
            ]],
            ['group' => 'reports', 'key' => 'filter_view_mode', 'values' => [
                'ar' => 'طريقة العرض',
                'en' => 'View mode',
                'fr' => 'Mode d\'affichage',
            ]],
            ['group' => 'reports', 'key' => 'event_type_label', 'values' => [
                'ar' => 'نوع العملية',
                'en' => 'Event type',
                'fr' => 'Type d\'événement',
            ]],
            ['group' => 'reports', 'key' => 'event_type.created', 'values' => [
                'ar' => 'إنشاء',
                'en' => 'Created',
                'fr' => 'Création',
            ]],
            ['group' => 'reports', 'key' => 'event_type.workflow', 'values' => [
                'ar' => 'سير العمل',
                'en' => 'Workflow',
                'fr' => 'Flux de travail',
            ]],
            ['group' => 'reports', 'key' => 'event_type.attachment', 'values' => [
                'ar' => 'مرفقات',
                'en' => 'Attachments',
                'fr' => 'Pièces jointes',
            ]],
            ['group' => 'reports', 'key' => 'event_type.audit', 'values' => [
                'ar' => 'سجل النظام',
                'en' => 'System audit',
                'fr' => 'Audit système',
            ]],
            ['group' => 'reports', 'key' => 'event_type.access', 'values' => [
                'ar' => 'عرض/تحميل/طباعة',
                'en' => 'View/download/print',
                'fr' => 'Affichage/téléchargement/impression',
            ]],
            ['group' => 'reports', 'key' => 'event_type.lending', 'values' => [
                'ar' => 'إعارة',
                'en' => 'Lending',
                'fr' => 'Prêt',
            ]],
            ['group' => 'reports', 'key' => 'view_mode.timeline', 'values' => [
                'ar' => 'سجل زمني كامل',
                'en' => 'Full timeline',
                'fr' => 'Chronologie complète',
            ]],
            ['group' => 'reports', 'key' => 'view_mode.by_user', 'values' => [
                'ar' => 'حسب المستخدم',
                'en' => 'By user',
                'fr' => 'Par utilisateur',
            ]],
            ['group' => 'reports', 'key' => 'view_mode.by_transaction', 'values' => [
                'ar' => 'حسب المعاملة',
                'en' => 'By transaction',
                'fr' => 'Par transaction',
            ]],
            ['group' => 'reports', 'key' => 'scope.user', 'values' => [
                'ar' => 'المستخدم: :name',
                'en' => 'User: :name',
                'fr' => 'Utilisateur : :name',
            ]],
            ['group' => 'reports', 'key' => 'scope.folder', 'values' => [
                'ar' => 'المجلد: :name',
                'en' => 'Folder: :name',
                'fr' => 'Dossier : :name',
            ]],
            ['group' => 'reports', 'key' => 'scope.transaction_search', 'values' => [
                'ar' => 'بحث معاملة: :q',
                'en' => 'Transaction search: :q',
                'fr' => 'Recherche transaction : :q',
            ]],
            ['group' => 'reports', 'key' => 'scope.event_type', 'values' => [
                'ar' => 'نوع العملية: :type',
                'en' => 'Event type: :type',
                'fr' => 'Type d\'événement : :type',
            ]],
            ['group' => 'reports', 'key' => 'scope.view_mode', 'values' => [
                'ar' => 'العرض: :mode',
                'en' => 'View: :mode',
                'fr' => 'Affichage : :mode',
            ]],
            ['group' => 'reports', 'key' => 'access_action.view', 'values' => [
                'ar' => 'عرض',
                'en' => 'View',
                'fr' => 'Affichage',
            ]],
            ['group' => 'reports', 'key' => 'access_action.download', 'values' => [
                'ar' => 'تحميل',
                'en' => 'Download',
                'fr' => 'Téléchargement',
            ]],
            ['group' => 'reports', 'key' => 'access_action.print', 'values' => [
                'ar' => 'طباعة',
                'en' => 'Print',
                'fr' => 'Impression',
            ]],
            ['group' => 'reports', 'key' => 'export.sheet.by_event_type', 'values' => [
                'ar' => 'حسب نوع العملية',
                'en' => 'By event type',
                'fr' => 'Par type d\'événement',
            ]],
            ['group' => 'reports', 'key' => 'export.sheet.operations_log', 'values' => [
                'ar' => 'سجل العمليات',
                'en' => 'Operations log',
                'fr' => 'Journal des opérations',
            ]],
            ['group' => 'reports', 'key' => 'export.col.details', 'values' => [
                'ar' => 'التفاصيل',
                'en' => 'Details',
                'fr' => 'Détails',
            ]],
        ];
    }

    public function up(): void
    {
        $admin = Role::where('slug', 'admin')->first();
        if ($admin) {
            $admin->update([
                'permissions' => array_values(array_unique(array_merge(
                    $admin->permissions ?? [],
                    [$this->permission()]
                ))),
            ]);
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('translation_keys')
            || ! \Illuminate\Support\Facades\Schema::hasTable('translations')
            || ! \Illuminate\Support\Facades\Schema::hasTable('languages')) {
            return;
        }

        $languages = Language::query()->pluck('id', 'code');
        if ($languages->isEmpty()) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
            );

            foreach ($entry['values'] as $code => $value) {
                if (! isset($languages[$code])) {
                    continue;
                }

                Translation::query()->updateOrCreate(
                    ['translation_key_id' => $translationKey->id, 'language_id' => $languages[$code]],
                    ['value' => $value],
                );
            }
        }
    }

    public function down(): void
    {
        $permission = $this->permission();

        foreach (Role::all() as $role) {
            $role->update([
                'permissions' => array_values(array_filter(
                    $role->permissions ?? [],
                    fn (string $p) => $p !== $permission
                )),
            ]);
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('translation_keys')
            || ! \Illuminate\Support\Facades\Schema::hasTable('translations')) {
            return;
        }

        foreach ($this->entries() as $entry) {
            $translationKey = TranslationKey::query()
                ->where('group', $entry['group'])
                ->where('key', $entry['key'])
                ->first();

            if ($translationKey) {
                Translation::query()->where('translation_key_id', $translationKey->id)->delete();
                $translationKey->delete();
            }
        }
    }
};