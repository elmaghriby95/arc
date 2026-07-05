<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Seeder;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $languages = Language::query()->pluck('id', 'code');

        if ($languages->isEmpty()) {
            return;
        }

        foreach (self::entries() as $entry) {
            $translationKey = TranslationKey::query()->firstOrCreate(
                ['group' => $entry['group'], 'key' => $entry['key']],
                ['description' => $entry['description'] ?? null],
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

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    public static function entries(): array
    {
        return array_merge(
            self::navEntries(),
            self::authEntries(),
            self::commonEntries(),
            self::messagesEntries(),
            self::settingsEntries(),
            self::dashboardEntries(),
            self::permissionsEntries(),
            self::languagesEntries(),
            self::translationsEntries(),
            self::profileEntries(),
        );
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function navEntries(): array
    {
        return [
            ['group' => 'nav', 'key' => 'tagline', 'values' => ['ar' => 'إدارة الوثائق والأرشفة', 'en' => 'Document & Archive Management', 'fr' => 'Gestion des documents et archives']],
            ['group' => 'nav', 'key' => 'dashboard', 'values' => ['ar' => 'لوحة التحكم', 'en' => 'Dashboard', 'fr' => 'Tableau de bord']],
            ['group' => 'nav', 'key' => 'documents', 'values' => ['ar' => 'الوثائق', 'en' => 'Documents', 'fr' => 'Documents']],
            ['group' => 'nav', 'key' => 'transactions', 'values' => ['ar' => 'إدارة الأرشفة', 'en' => 'Archive Management', 'fr' => 'Gestion des archives']],
            ['group' => 'nav', 'key' => 'departments', 'values' => ['ar' => 'الأقسام', 'en' => 'Departments', 'fr' => 'Départements']],
            ['group' => 'nav', 'key' => 'categories', 'values' => ['ar' => 'التصنيفات', 'en' => 'Categories', 'fr' => 'Catégories']],
            ['group' => 'nav', 'key' => 'settings', 'values' => ['ar' => 'الإعدادات', 'en' => 'Settings', 'fr' => 'Paramètres']],
            ['group' => 'nav', 'key' => 'notifications', 'values' => ['ar' => 'الإشعارات', 'en' => 'Notifications', 'fr' => 'Notifications']],
            ['group' => 'nav', 'key' => 'unread_count', 'values' => ['ar' => ':count غير مقروء', 'en' => ':count unread', 'fr' => ':count non lu(s)']],
            ['group' => 'nav', 'key' => 'no_new_notifications', 'values' => ['ar' => 'لا إشعارات جديدة', 'en' => 'No new notifications', 'fr' => 'Aucune nouvelle notification']],
            ['group' => 'nav', 'key' => 'mark_all_read', 'values' => ['ar' => 'تعليم الكل', 'en' => 'Mark all read', 'fr' => 'Tout marquer comme lu']],
            ['group' => 'nav', 'key' => 'no_notifications', 'values' => ['ar' => 'لا توجد إشعارات', 'en' => 'No notifications', 'fr' => 'Aucune notification']],
            ['group' => 'nav', 'key' => 'notifications_empty_hint', 'values' => ['ar' => 'ستظهر هنا تحديثات حالة المعاملات والأرشفة فور حدوثها.', 'en' => 'Transaction and archive status updates will appear here.', 'fr' => 'Les mises à jour apparaîtront ici.']],
            ['group' => 'nav', 'key' => 'last_notifications', 'values' => ['ar' => 'آخر :count إشعار', 'en' => 'Last :count notifications', 'fr' => 'Dernières :count notifications']],
            ['group' => 'nav', 'key' => 'profile', 'values' => ['ar' => 'الملف الشخصي', 'en' => 'Profile', 'fr' => 'Profil']],
            ['group' => 'nav', 'key' => 'logout', 'values' => ['ar' => 'تسجيل الخروج', 'en' => 'Log Out', 'fr' => 'Déconnexion']],
            ['group' => 'nav', 'key' => 'menu', 'values' => ['ar' => 'القائمة', 'en' => 'Menu', 'fr' => 'Menu']],
            ['group' => 'nav', 'key' => 'language', 'values' => ['ar' => 'اللغة', 'en' => 'Language', 'fr' => 'Langue']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function authEntries(): array
    {
        return [
            ['group' => 'auth', 'key' => 'login', 'values' => ['ar' => 'تسجيل الدخول', 'en' => 'Log in', 'fr' => 'Connexion']],
            ['group' => 'auth', 'key' => 'register', 'values' => ['ar' => 'إنشاء حساب', 'en' => 'Register', 'fr' => 'Inscription']],
            ['group' => 'auth', 'key' => 'email', 'values' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email', 'fr' => 'E-mail']],
            ['group' => 'auth', 'key' => 'password', 'values' => ['ar' => 'كلمة المرور', 'en' => 'Password', 'fr' => 'Mot de passe']],
            ['group' => 'auth', 'key' => 'remember_me', 'values' => ['ar' => 'تذكرني', 'en' => 'Remember me', 'fr' => 'Se souvenir de moi']],
            ['group' => 'auth', 'key' => 'forgot_password', 'values' => ['ar' => 'نسيت كلمة المرور؟', 'en' => 'Forgot your password?', 'fr' => 'Mot de passe oublié ?']],
            ['group' => 'auth', 'key' => 'name', 'values' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom']],
            ['group' => 'auth', 'key' => 'confirm_password', 'values' => ['ar' => 'تأكيد كلمة المرور', 'en' => 'Confirm Password', 'fr' => 'Confirmer le mot de passe']],
            ['group' => 'auth', 'key' => 'already_registered', 'values' => ['ar' => 'لديك حساب بالفعل؟', 'en' => 'Already registered?', 'fr' => 'Déjà inscrit ?']],
            ['group' => 'auth', 'key' => 'logged_in', 'values' => ['ar' => 'تم تسجيل الدخول بنجاح!', 'en' => "You're logged in!", 'fr' => 'Vous êtes connecté !']],
            ['group' => 'auth', 'key' => 'welcome_back', 'values' => ['ar' => 'مرحباً بعودتك', 'en' => 'Welcome back', 'fr' => 'Bon retour']],
            ['group' => 'auth', 'key' => 'login_desc', 'values' => ['ar' => 'سجّل دخولك للوصول إلى لوحة التحكم وإدارة الأرشيف', 'en' => 'Sign in to access the dashboard and archive', 'fr' => 'Connectez-vous pour accéder au tableau de bord']],
            ['group' => 'auth', 'key' => 'no_account', 'values' => ['ar' => 'ليس لديك حساب؟', 'en' => "Don't have an account?", 'fr' => 'Pas de compte ?']],
            ['group' => 'auth', 'key' => 'show_password', 'values' => ['ar' => 'إظهار كلمة المرور', 'en' => 'Show password', 'fr' => 'Afficher le mot de passe']],
            ['group' => 'auth', 'key' => 'login_page_title', 'values' => ['ar' => 'تسجيل الدخول', 'en' => 'Log in', 'fr' => 'Connexion']],
            ['group' => 'auth', 'key' => 'brand_subtitle', 'values' => ['ar' => 'منصة متكاملة لإدارة وأرشفة الوثائق الإلكترونية بأمان وكفاءة', 'en' => 'Integrated platform for secure document archiving', 'fr' => 'Plateforme intégrée pour l\'archivage sécurisé']],
            ['group' => 'auth', 'key' => 'feature_1', 'values' => ['ar' => 'أرشفة مركزية للوثائق والمراسلات', 'en' => 'Central archiving for documents and correspondence', 'fr' => 'Archivage central des documents']],
            ['group' => 'auth', 'key' => 'feature_2', 'values' => ['ar' => 'بحث سريع وتصنيف ذكي', 'en' => 'Fast search and smart classification', 'fr' => 'Recherche rapide et classification']],
            ['group' => 'auth', 'key' => 'feature_3', 'values' => ['ar' => 'تتبع الإصدارات وسجل العمليات', 'en' => 'Version tracking and audit log', 'fr' => 'Suivi des versions et journal d\'audit']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function commonEntries(): array
    {
        return [
            ['group' => 'common', 'key' => 'save', 'values' => ['ar' => 'حفظ', 'en' => 'Save', 'fr' => 'Enregistrer']],
            ['group' => 'common', 'key' => 'cancel', 'values' => ['ar' => 'إلغاء', 'en' => 'Cancel', 'fr' => 'Annuler']],
            ['group' => 'common', 'key' => 'delete', 'values' => ['ar' => 'حذف', 'en' => 'Delete', 'fr' => 'Supprimer']],
            ['group' => 'common', 'key' => 'edit', 'values' => ['ar' => 'تعديل', 'en' => 'Edit', 'fr' => 'Modifier']],
            ['group' => 'common', 'key' => 'add', 'values' => ['ar' => 'إضافة', 'en' => 'Add', 'fr' => 'Ajouter']],
            ['group' => 'common', 'key' => 'back', 'values' => ['ar' => '← العودة للإعدادات', 'en' => '← Back to settings', 'fr' => '← Retour aux paramètres']],
            ['group' => 'common', 'key' => 'view_all', 'values' => ['ar' => 'عرض الكل', 'en' => 'View all', 'fr' => 'Tout voir']],
            ['group' => 'common', 'key' => 'confirm_delete', 'values' => ['ar' => 'هل أنت متأكد؟', 'en' => 'Are you sure?', 'fr' => 'Êtes-vous sûr ?']],
            ['group' => 'common', 'key' => 'active', 'values' => ['ar' => 'نشطة', 'en' => 'Active', 'fr' => 'Active']],
            ['group' => 'common', 'key' => 'inactive', 'values' => ['ar' => 'غير نشطة', 'en' => 'Inactive', 'fr' => 'Inactive']],
            ['group' => 'common', 'key' => 'default', 'values' => ['ar' => 'افتراضية', 'en' => 'Default', 'fr' => 'Par défaut']],
            ['group' => 'common', 'key' => 'yes', 'values' => ['ar' => 'نعم', 'en' => 'Yes', 'fr' => 'Oui']],
            ['group' => 'common', 'key' => 'no', 'values' => ['ar' => 'لا', 'en' => 'No', 'fr' => 'Non']],
            ['group' => 'common', 'key' => 'search', 'values' => ['ar' => 'بحث', 'en' => 'Search', 'fr' => 'Rechercher']],
            ['group' => 'common', 'key' => 'actions', 'values' => ['ar' => 'إجراءات', 'en' => 'Actions', 'fr' => 'Actions']],
            ['group' => 'common', 'key' => 'status', 'values' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut']],
            ['group' => 'common', 'key' => 'date', 'values' => ['ar' => 'التاريخ', 'en' => 'Date', 'fr' => 'Date']],
            ['group' => 'common', 'key' => 'description', 'values' => ['ar' => 'الوصف', 'en' => 'Description', 'fr' => 'Description']],
            ['group' => 'common', 'key' => 'name', 'values' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom']],
            ['group' => 'common', 'key' => 'email', 'values' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email', 'fr' => 'E-mail']],
            ['group' => 'common', 'key' => 'role', 'values' => ['ar' => 'الدور', 'en' => 'Role', 'fr' => 'Rôle']],
            ['group' => 'common', 'key' => 'department', 'values' => ['ar' => 'القسم', 'en' => 'Department', 'fr' => 'Département']],
            ['group' => 'common', 'key' => 'direction_rtl', 'values' => ['ar' => 'من اليمين لليسار (RTL)', 'en' => 'Right to left (RTL)', 'fr' => 'Droite à gauche (RTL)']],
            ['group' => 'common', 'key' => 'direction_ltr', 'values' => ['ar' => 'من اليسار لليمين (LTR)', 'en' => 'Left to right (LTR)', 'fr' => 'Gauche à droite (LTR)']],
            ['group' => 'common', 'key' => 'direction_rtl_short', 'values' => ['ar' => 'من اليمين لليسار', 'en' => 'Right to left', 'fr' => 'Droite à gauche']],
            ['group' => 'common', 'key' => 'direction_ltr_short', 'values' => ['ar' => 'من اليسار لليمين', 'en' => 'Left to right', 'fr' => 'Gauche à droite']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function messagesEntries(): array
    {
        return [
            ['group' => 'messages', 'key' => 'language_added', 'values' => ['ar' => 'تم إضافة اللغة بنجاح.', 'en' => 'Language added successfully.', 'fr' => 'Langue ajoutée avec succès.']],
            ['group' => 'messages', 'key' => 'language_updated', 'values' => ['ar' => 'تم تحديث اللغة بنجاح.', 'en' => 'Language updated successfully.', 'fr' => 'Langue mise à jour avec succès.']],
            ['group' => 'messages', 'key' => 'language_deleted', 'values' => ['ar' => 'تم حذف اللغة بنجاح.', 'en' => 'Language deleted successfully.', 'fr' => 'Langue supprimée avec succès.']],
            ['group' => 'messages', 'key' => 'cannot_delete_default_language', 'values' => ['ar' => 'لا يمكن حذف اللغة الافتراضية.', 'en' => 'Cannot delete the default language.', 'fr' => 'Impossible de supprimer la langue par défaut.']],
            ['group' => 'messages', 'key' => 'translations_saved', 'values' => ['ar' => 'تم حفظ الترجمات بنجاح.', 'en' => 'Translations saved successfully.', 'fr' => 'Traductions enregistrées avec succès.']],
            ['group' => 'messages', 'key' => 'translation_key_saved', 'values' => ['ar' => 'تم إضافة الكلمة بنجاح.', 'en' => 'Translation key saved successfully.', 'fr' => 'Clé de traduction enregistrée.']],
            ['group' => 'messages', 'key' => 'translation_key_deleted', 'values' => ['ar' => 'تم حذف الكلمة بنجاح.', 'en' => 'Translation key deleted successfully.', 'fr' => 'Clé de traduction supprimée.']],
            ['group' => 'messages', 'key' => 'locale_switched', 'values' => ['ar' => 'تم تغيير اللغة.', 'en' => 'Language changed.', 'fr' => 'Langue modifiée.']],
            ['group' => 'messages', 'key' => 'no_access', 'values' => ['ar' => 'لا تملك صلاحية الوصول إلى أي صفحة في النظام. تواصل مع مدير النظام.', 'en' => 'You do not have access to any page. Contact your administrator.', 'fr' => "Vous n'avez accès à aucune page. Contactez l'administrateur."]],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function settingsEntries(): array
    {
        return [
            ['group' => 'settings', 'key' => 'title', 'values' => ['ar' => 'الإعدادات', 'en' => 'Settings', 'fr' => 'Paramètres']],
            ['group' => 'settings', 'key' => 'subtitle', 'values' => ['ar' => 'إدارة المستخدمين والأدوار والهيكل التنظيمي وإعدادات الأرشيف', 'en' => 'Manage users, roles, organization and archive settings', 'fr' => 'Gérer utilisateurs, rôles, organisation et archives']],
            ['group' => 'settings', 'key' => 'hero_title', 'values' => ['ar' => 'مركز التحكم', 'en' => 'Control Center', 'fr' => 'Centre de contrôle']],
            ['group' => 'settings', 'key' => 'hero_desc', 'values' => ['ar' => 'اختر أحد الأقسام أدناه لإدارة إعدادات النظام والصلاحيات', 'en' => 'Choose a section below to manage system settings and permissions', 'fr' => 'Choisissez une section ci-dessous']],
            ['group' => 'settings', 'key' => 'users_count', 'values' => ['ar' => 'مستخدم', 'en' => 'user(s)', 'fr' => 'utilisateur(s)']],
            ['group' => 'settings', 'key' => 'departments_count', 'values' => ['ar' => 'قسم', 'en' => 'department(s)', 'fr' => 'département(s)']],
            ['group' => 'settings', 'key' => 'roles_count', 'values' => ['ar' => 'دور', 'en' => 'role(s)', 'fr' => 'rôle(s)']],
            ['group' => 'settings', 'key' => 'system_management', 'values' => ['ar' => 'إدارة النظام', 'en' => 'System Management', 'fr' => 'Gestion du système']],
            ['group' => 'settings', 'key' => 'archive_settings', 'values' => ['ar' => 'إعدادات الأرشيف', 'en' => 'Archive Settings', 'fr' => 'Paramètres des archives']],
            ['group' => 'settings', 'key' => 'users_title', 'values' => ['ar' => 'إدارة المستخدمين', 'en' => 'User Management', 'fr' => 'Gestion des utilisateurs']],
            ['group' => 'settings', 'key' => 'users_desc', 'values' => ['ar' => 'عرض وإدارة حسابات المستخدمين، تعيين الأقسام، ومتابعة النشاط', 'en' => 'View and manage user accounts, departments and activity', 'fr' => 'Gérer les comptes utilisateurs']],
            ['group' => 'settings', 'key' => 'users_badge', 'values' => ['ar' => ':count مستخدم', 'en' => ':count user(s)', 'fr' => ':count utilisateur(s)']],
            ['group' => 'settings', 'key' => 'roles_title', 'values' => ['ar' => 'إدارة الأدوار', 'en' => 'Role Management', 'fr' => 'Gestion des rôles']],
            ['group' => 'settings', 'key' => 'roles_desc', 'values' => ['ar' => 'إنشاء أدوار مخصصة وتحديد صلاحيات كل إجراء في النظام', 'en' => 'Create custom roles and define permissions', 'fr' => 'Créer des rôles et définir les permissions']],
            ['group' => 'settings', 'key' => 'org_title', 'values' => ['ar' => 'الهيكل التنظيمي', 'en' => 'Organization Structure', 'fr' => 'Structure organisationnelle']],
            ['group' => 'settings', 'key' => 'org_desc', 'values' => ['ar' => 'شجرة مرنة للقطاعات والإدارات والأقسام مع تعيين المدراء', 'en' => 'Flexible tree of sectors, departments and managers', 'fr' => 'Arbre flexible des unités organisationnelles']],
            ['group' => 'settings', 'key' => 'org_badge', 'values' => ['ar' => ':count وحدة تنظيمية', 'en' => ':count org unit(s)', 'fr' => ':count unité(s) org']],
            ['group' => 'settings', 'key' => 'txn_statuses_title', 'values' => ['ar' => 'حالات المعاملات', 'en' => 'Transaction Statuses', 'fr' => 'Statuts des transactions']],
            ['group' => 'settings', 'key' => 'txn_statuses_desc', 'values' => ['ar' => 'تسلسل سير عمل المعاملات وصلاحيات الانتقال بين الحالات', 'en' => 'Workflow sequence and status transition permissions', 'fr' => 'Séquence du workflow et permissions']],
            ['group' => 'settings', 'key' => 'txn_statuses_badge', 'values' => ['ar' => ':count حالة نشطة', 'en' => ':count active status(es)', 'fr' => ':count statut(s) actif(s)']],
            ['group' => 'settings', 'key' => 'doc_types_title', 'values' => ['ar' => 'أنواع المستندات', 'en' => 'Document Types', 'fr' => 'Types de documents']],
            ['group' => 'settings', 'key' => 'doc_types_desc', 'values' => ['ar' => 'تصنيف أنواع الوثائق مثل الخطابات والعقود والتقارير', 'en' => 'Classify document types like letters, contracts, reports', 'fr' => 'Classer les types de documents']],
            ['group' => 'settings', 'key' => 'doc_types_badge', 'values' => ['ar' => ':count نوع نشط', 'en' => ':count active type(s)', 'fr' => ':count type(s) actif(s)']],
            ['group' => 'settings', 'key' => 'txn_types_title', 'values' => ['ar' => 'أنواع المعاملات', 'en' => 'Transaction Types', 'fr' => 'Types de transactions']],
            ['group' => 'settings', 'key' => 'txn_types_desc', 'values' => ['ar' => 'تصنيف المعاملات الواردة والصادرة والداخلية', 'en' => 'Classify incoming, outgoing and internal transactions', 'fr' => 'Classer les transactions']],
            ['group' => 'settings', 'key' => 'txn_types_badge', 'values' => ['ar' => ':count نوع نشط', 'en' => ':count active type(s)', 'fr' => ':count type(s) actif(s)']],
            ['group' => 'settings', 'key' => 'folders_title', 'values' => ['ar' => 'شجرة المجلدات', 'en' => 'Folder Tree', 'fr' => 'Arborescence des dossiers']],
            ['group' => 'settings', 'key' => 'folders_desc', 'values' => ['ar' => 'هيكل المجلدات لتنظيم وتصنيف الوثائق المؤرشفة', 'en' => 'Folder structure for organizing archived documents', 'fr' => 'Structure des dossiers pour les archives']],
            ['group' => 'settings', 'key' => 'folders_badge', 'values' => ['ar' => ':count مجلد نشط', 'en' => ':count active folder(s)', 'fr' => ':count dossier(s) actif(s)']],
            ['group' => 'settings', 'key' => 'languages_title', 'values' => ['ar' => 'اللغات', 'en' => 'Languages', 'fr' => 'Langues']],
            ['group' => 'settings', 'key' => 'languages_desc', 'values' => ['ar' => 'إدارة لغات واجهة النظام واتجاه العرض RTL/LTR', 'en' => 'Manage UI languages and RTL/LTR direction', 'fr' => 'Gérer les langues et la direction RTL/LTR']],
            ['group' => 'settings', 'key' => 'languages_badge', 'values' => ['ar' => ':count لغة نشطة', 'en' => ':count active language(s)', 'fr' => ':count langue(s) active(s)']],
            ['group' => 'settings', 'key' => 'ref_numbers_title', 'values' => ['ar' => 'إعدادات الرقم الإشاري', 'en' => 'Reference Number Settings', 'fr' => 'Paramètres des numéros de référence']],
            ['group' => 'settings', 'key' => 'ref_numbers_desc', 'values' => ['ar' => 'قواعد الأرقام الإشارية، الرقم التشغيلي، منع التكرار، والتدقيق', 'en' => 'Reference number rules, operational numbers, deduplication', 'fr' => 'Règles des numéros de référence']],
            ['group' => 'settings', 'key' => 'ref_numbers_badge', 'values' => ['ar' => 'تكوين النظام', 'en' => 'System configuration', 'fr' => 'Configuration système']],
            ['group' => 'settings', 'key' => 'recent_users', 'values' => ['ar' => 'آخر المستخدمين المسجّلين', 'en' => 'Recently registered users', 'fr' => 'Utilisateurs récemment inscrits']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function dashboardEntries(): array
    {
        return [
            ['group' => 'dashboard', 'key' => 'welcome', 'values' => ['ar' => 'مرحباً :name', 'en' => 'Welcome :name', 'fr' => 'Bienvenue :name']],
            ['group' => 'dashboard', 'key' => 'create_transaction', 'values' => ['ar' => 'إنشاء معاملة', 'en' => 'Create transaction', 'fr' => 'Créer une transaction']],
            ['group' => 'dashboard', 'key' => 'hero_title', 'values' => ['ar' => 'منظومة الأرشفة الإلكترونية', 'en' => 'Electronic Archiving System', 'fr' => "Système d'archivage électronique"]],
            ['group' => 'dashboard', 'key' => 'hero_desc', 'values' => ['ar' => 'إدارة المعاملات ومستنداتها المرفقة مع بحث سريع وتتبع سير العمل.', 'en' => 'Manage transactions and attachments with quick search and workflow tracking.', 'fr' => 'Gérer transactions et pièces jointes.']],
            ['group' => 'dashboard', 'key' => 'attached_documents', 'values' => ['ar' => 'مستند مرفق', 'en' => 'attached document(s)', 'fr' => 'document(s) joint(s)']],
            ['group' => 'dashboard', 'key' => 'total_documents', 'values' => ['ar' => 'إجمالي الوثائق', 'en' => 'Total documents', 'fr' => 'Total documents']],
            ['group' => 'dashboard', 'key' => 'active_departments', 'values' => ['ar' => 'الأقسام النشطة', 'en' => 'Active departments', 'fr' => 'Départements actifs']],
            ['group' => 'dashboard', 'key' => 'categories', 'values' => ['ar' => 'التصنيفات', 'en' => 'Categories', 'fr' => 'Catégories']],
            ['group' => 'dashboard', 'key' => 'users', 'values' => ['ar' => 'المستخدمون', 'en' => 'Users', 'fr' => 'Utilisateurs']],
            ['group' => 'dashboard', 'key' => 'recent_documents', 'values' => ['ar' => 'أحدث الوثائق', 'en' => 'Recent documents', 'fr' => 'Documents récents']],
            ['group' => 'dashboard', 'key' => 'recent_documents_sub', 'values' => ['ar' => 'آخر المستندات المرفقة بالمعاملات', 'en' => 'Latest documents attached to transactions', 'fr' => 'Derniers documents joints']],
            ['group' => 'dashboard', 'key' => 'no_documents', 'values' => ['ar' => 'لا توجد مستندات بعد. أنشئ معاملة وارفع المستندات المطلوبة.', 'en' => 'No documents yet. Create a transaction and upload documents.', 'fr' => 'Aucun document. Créez une transaction.']],
            ['group' => 'dashboard', 'key' => 'document', 'values' => ['ar' => 'المستند', 'en' => 'Document', 'fr' => 'Document']],
            ['group' => 'dashboard', 'key' => 'transaction', 'values' => ['ar' => 'المعاملة', 'en' => 'Transaction', 'fr' => 'Transaction']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function languagesEntries(): array
    {
        return [
            ['group' => 'languages', 'key' => 'title', 'values' => ['ar' => 'اللغات', 'en' => 'Languages', 'fr' => 'Langues']],
            ['group' => 'languages', 'key' => 'subtitle', 'values' => ['ar' => 'إدارة لغات واجهة النظام والمحتوى', 'en' => 'Manage UI and content languages', 'fr' => 'Gérer les langues de l\'interface']],
            ['group' => 'languages', 'key' => 'add_title', 'values' => ['ar' => 'إضافة لغة', 'en' => 'Add language', 'fr' => 'Ajouter une langue']],
            ['group' => 'languages', 'key' => 'native_name', 'values' => ['ar' => 'الاسم الأصلي', 'en' => 'Native name', 'fr' => 'Nom natif']],
            ['group' => 'languages', 'key' => 'code', 'values' => ['ar' => 'رمز اللغة', 'en' => 'Language code', 'fr' => 'Code langue']],
            ['group' => 'languages', 'key' => 'direction', 'values' => ['ar' => 'الاتجاه', 'en' => 'Direction', 'fr' => 'Direction']],
            ['group' => 'languages', 'key' => 'is_active', 'values' => ['ar' => 'نشطة', 'en' => 'Active', 'fr' => 'Active']],
            ['group' => 'languages', 'key' => 'is_default', 'values' => ['ar' => 'اللغة الافتراضية', 'en' => 'Default language', 'fr' => 'Langue par défaut']],
            ['group' => 'languages', 'key' => 'add_button', 'values' => ['ar' => 'إضافة لغة', 'en' => 'Add language', 'fr' => 'Ajouter']],
            ['group' => 'languages', 'key' => 'empty', 'values' => ['ar' => 'لا توجد لغات مضافة بعد.', 'en' => 'No languages added yet.', 'fr' => 'Aucune langue ajoutée.']],
            ['group' => 'languages', 'key' => 'manage_words', 'values' => ['ar' => 'إدارة الكلمات', 'en' => 'Manage words', 'fr' => 'Gérer les mots']],
            ['group' => 'languages', 'key' => 'default_badge', 'values' => ['ar' => 'افتراضية', 'en' => 'Default', 'fr' => 'Par défaut']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function translationsEntries(): array
    {
        return [
            ['group' => 'translations', 'key' => 'title', 'values' => ['ar' => 'كلمات اللغة', 'en' => 'Language words', 'fr' => 'Mots de la langue']],
            ['group' => 'translations', 'key' => 'subtitle', 'values' => ['ar' => 'إضافة وتعديل الكلمات المعروضة في واجهة النظام', 'en' => 'Add and edit words displayed in the UI', 'fr' => 'Ajouter et modifier les mots de l\'interface']],
            ['group' => 'translations', 'key' => 'add_word', 'values' => ['ar' => 'إضافة كلمة جديدة', 'en' => 'Add new word', 'fr' => 'Ajouter un mot']],
            ['group' => 'translations', 'key' => 'group', 'values' => ['ar' => 'المجموعة', 'en' => 'Group', 'fr' => 'Groupe']],
            ['group' => 'translations', 'key' => 'key', 'values' => ['ar' => 'المفتاح', 'en' => 'Key', 'fr' => 'Clé']],
            ['group' => 'translations', 'key' => 'value', 'values' => ['ar' => 'القيمة', 'en' => 'Value', 'fr' => 'Valeur']],
            ['group' => 'translations', 'key' => 'description', 'values' => ['ar' => 'الوصف (اختياري)', 'en' => 'Description (optional)', 'fr' => 'Description (optionnel)']],
            ['group' => 'translations', 'key' => 'group_hint', 'values' => ['ar' => 'مثال: nav, common, settings', 'en' => 'Example: nav, common, settings', 'fr' => 'Exemple : nav, common, settings']],
            ['group' => 'translations', 'key' => 'key_hint', 'values' => ['ar' => 'مثال: documents, save', 'en' => 'Example: documents, save', 'fr' => 'Exemple : documents, save']],
            ['group' => 'translations', 'key' => 'usage_hint', 'values' => ['ar' => 'يُستخدم في الكود كـ: group.key', 'en' => 'Used in code as: group.key', 'fr' => 'Utilisé dans le code : group.key']],
            ['group' => 'translations', 'key' => 'save_all', 'values' => ['ar' => 'حفظ جميع الترجمات', 'en' => 'Save all translations', 'fr' => 'Enregistrer toutes les traductions']],
            ['group' => 'translations', 'key' => 'empty', 'values' => ['ar' => 'لا توجد كلمات بعد. أضف كلمة جديدة أعلاه.', 'en' => 'No words yet. Add a new word above.', 'fr' => 'Aucun mot. Ajoutez-en un ci-dessus.']],
            ['group' => 'translations', 'key' => 'back_to_languages', 'values' => ['ar' => '← العودة للغات', 'en' => '← Back to languages', 'fr' => '← Retour aux langues']],
        ];
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function permissionsEntries(): array
    {
        $entries = [];
        $map = [
            'dashboard.view' => ['ar' => 'عرض لوحة التحكم', 'en' => 'View dashboard', 'fr' => 'Voir le tableau de bord'],
            'documents.view' => ['ar' => 'عرض الوثائق (مستندات المعاملات)', 'en' => 'View documents', 'fr' => 'Voir les documents'],
            'documents.create' => ['ar' => 'إضافة وثيقة', 'en' => 'Create document', 'fr' => 'Créer un document'],
            'documents.edit' => ['ar' => 'تعديل وثيقة', 'en' => 'Edit document', 'fr' => 'Modifier un document'],
            'documents.delete' => ['ar' => 'حذف وثيقة', 'en' => 'Delete document', 'fr' => 'Supprimer un document'],
            'documents.download' => ['ar' => 'تحميل وثيقة', 'en' => 'Download document', 'fr' => 'Télécharger un document'],
            'departments.view' => ['ar' => 'عرض الأقسام', 'en' => 'View departments', 'fr' => 'Voir les départements'],
            'departments.create' => ['ar' => 'إضافة قسم', 'en' => 'Create department', 'fr' => 'Créer un département'],
            'departments.edit' => ['ar' => 'تعديل قسم', 'en' => 'Edit department', 'fr' => 'Modifier un département'],
            'departments.delete' => ['ar' => 'حذف قسم', 'en' => 'Delete department', 'fr' => 'Supprimer un département'],
            'categories.view' => ['ar' => 'عرض التصنيفات', 'en' => 'View categories', 'fr' => 'Voir les catégories'],
            'categories.create' => ['ar' => 'إضافة تصنيف', 'en' => 'Create category', 'fr' => 'Créer une catégorie'],
            'categories.edit' => ['ar' => 'تعديل تصنيف', 'en' => 'Edit category', 'fr' => 'Modifier une catégorie'],
            'categories.delete' => ['ar' => 'حذف تصنيف', 'en' => 'Delete category', 'fr' => 'Supprimer une catégorie'],
            'settings.view' => ['ar' => 'عرض صفحة الإعدادات', 'en' => 'View settings', 'fr' => 'Voir les paramètres'],
            'settings.users.view' => ['ar' => 'عرض المستخدمين', 'en' => 'View users', 'fr' => 'Voir les utilisateurs'],
            'settings.users.create' => ['ar' => 'إنشاء مستخدم', 'en' => 'Create user', 'fr' => 'Créer un utilisateur'],
            'settings.users.edit' => ['ar' => 'تعديل المستخدمين', 'en' => 'Edit users', 'fr' => 'Modifier les utilisateurs'],
            'settings.roles.view' => ['ar' => 'عرض الأدوار', 'en' => 'View roles', 'fr' => 'Voir les rôles'],
            'settings.roles.create' => ['ar' => 'إنشاء دور', 'en' => 'Create role', 'fr' => 'Créer un rôle'],
            'settings.roles.edit' => ['ar' => 'تعديل دور', 'en' => 'Edit role', 'fr' => 'Modifier un rôle'],
            'settings.roles.delete' => ['ar' => 'حذف دور', 'en' => 'Delete role', 'fr' => 'Supprimer un rôle'],
            'settings.organization.view' => ['ar' => 'عرض الهيكل التنظيمي', 'en' => 'View organization', 'fr' => "Voir l'organisation"],
            'settings.languages.view' => ['ar' => 'عرض اللغات', 'en' => 'View languages', 'fr' => 'Voir les langues'],
            'settings.languages.create' => ['ar' => 'إضافة لغة', 'en' => 'Add language', 'fr' => 'Ajouter une langue'],
            'settings.languages.edit' => ['ar' => 'تعديل لغة', 'en' => 'Edit language', 'fr' => 'Modifier une langue'],
            'settings.languages.delete' => ['ar' => 'حذف لغة', 'en' => 'Delete language', 'fr' => 'Supprimer une langue'],
        ];

        foreach ($map as $slug => $values) {
            $entries[] = [
                'group' => 'permissions',
                'key' => str_replace('.', '_', $slug),
                'description' => $slug,
                'values' => $values,
            ];
        }

        return $entries;
    }

    /** @return list<array{group: string, key: string, description?: string, values: array<string, string>}> */
    private static function profileEntries(): array
    {
        return [
            ['group' => 'profile', 'key' => 'title', 'values' => ['ar' => 'الملف الشخصي', 'en' => 'Profile', 'fr' => 'Profil']],
            ['group' => 'profile', 'key' => 'subtitle', 'values' => ['ar' => 'إدارة حسابك ومتابعة نشاطك في النظام', 'en' => 'Manage your account and track your activity', 'fr' => 'Gérez votre compte et suivez votre activité']],
            ['group' => 'profile', 'key' => 'change_avatar', 'values' => ['ar' => 'تغيير الصورة', 'en' => 'Change photo', 'fr' => 'Changer la photo']],
            ['group' => 'profile', 'key' => 'remove_avatar', 'values' => ['ar' => 'إزالة الصورة', 'en' => 'Remove photo', 'fr' => 'Supprimer la photo']],
            ['group' => 'profile', 'key' => 'remove_avatar_confirm', 'values' => ['ar' => 'هل تريد إزالة صورتك الشخصية؟', 'en' => 'Remove your profile photo?', 'fr' => 'Supprimer votre photo de profil ?']],
            ['group' => 'profile', 'key' => 'last_login', 'values' => ['ar' => 'آخر دخول', 'en' => 'Last login', 'fr' => 'Dernière connexion']],
            ['group' => 'profile', 'key' => 'no_login_yet', 'values' => ['ar' => 'لم يسجل دخولاً بعد', 'en' => 'No login recorded yet', 'fr' => 'Aucune connexion enregistrée']],
            ['group' => 'profile', 'key' => 'member_since', 'values' => ['ar' => 'عضو منذ', 'en' => 'Member since', 'fr' => 'Membre depuis']],
            ['group' => 'profile', 'key' => 'org_data', 'values' => ['ar' => 'البيانات التنظيمية', 'en' => 'Organizational data', 'fr' => 'Données organisationnelles']],
            ['group' => 'profile', 'key' => 'role', 'values' => ['ar' => 'الدور', 'en' => 'Role', 'fr' => 'Rôle']],
            ['group' => 'profile', 'key' => 'department', 'values' => ['ar' => 'الموقع التنظيمي', 'en' => 'Organizational unit', 'fr' => 'Unité organisationnelle']],
            ['group' => 'profile', 'key' => 'language', 'values' => ['ar' => 'اللغة', 'en' => 'Language', 'fr' => 'Langue']],
            ['group' => 'profile', 'key' => 'default_language', 'values' => ['ar' => 'اللغة الافتراضية', 'en' => 'Default language', 'fr' => 'Langue par défaut']],
            ['group' => 'profile', 'key' => 'email_status', 'values' => ['ar' => 'حالة البريد', 'en' => 'Email status', 'fr' => 'Statut e-mail']],
            ['group' => 'profile', 'key' => 'email_verified', 'values' => ['ar' => 'موثّق', 'en' => 'Verified', 'fr' => 'Vérifié']],
            ['group' => 'profile', 'key' => 'email_unverified', 'values' => ['ar' => 'غير موثّق', 'en' => 'Unverified', 'fr' => 'Non vérifié']],
            ['group' => 'profile', 'key' => 'account_created', 'values' => ['ar' => 'تاريخ إنشاء الحساب', 'en' => 'Account created', 'fr' => 'Compte créé']],
            ['group' => 'profile', 'key' => 'last_updated', 'values' => ['ar' => 'آخر تحديث', 'en' => 'Last updated', 'fr' => 'Dernière mise à jour']],
            ['group' => 'profile', 'key' => 'activity_log', 'values' => ['ar' => 'سجل العمليات', 'en' => 'Activity log', 'fr' => 'Journal d\'activité']],
            ['group' => 'profile', 'key' => 'activity_log_desc', 'values' => ['ar' => 'جميع عملياتك في النظام', 'en' => 'All your actions in the system', 'fr' => 'Toutes vos actions dans le système']],
            ['group' => 'profile', 'key' => 'no_activity', 'values' => ['ar' => 'لا توجد عمليات مسجّلة بعد', 'en' => 'No activity recorded yet', 'fr' => 'Aucune activité enregistrée']],
            ['group' => 'profile', 'key' => 'tab.info', 'values' => ['ar' => 'البيانات الشخصية', 'en' => 'Personal info', 'fr' => 'Informations personnelles']],
            ['group' => 'profile', 'key' => 'tab.password', 'values' => ['ar' => 'كلمة المرور', 'en' => 'Password', 'fr' => 'Mot de passe']],
            ['group' => 'profile', 'key' => 'tab.danger', 'values' => ['ar' => 'حذف الحساب', 'en' => 'Delete account', 'fr' => 'Supprimer le compte']],
            ['group' => 'profile', 'key' => 'info_title', 'values' => ['ar' => 'معلومات الحساب', 'en' => 'Account information', 'fr' => 'Informations du compte']],
            ['group' => 'profile', 'key' => 'info_desc', 'values' => ['ar' => 'حدّث اسمك وبريدك الإلكتروني', 'en' => 'Update your name and email address', 'fr' => 'Mettez à jour votre nom et e-mail']],
            ['group' => 'profile', 'key' => 'name', 'values' => ['ar' => 'الاسم', 'en' => 'Name', 'fr' => 'Nom']],
            ['group' => 'profile', 'key' => 'email', 'values' => ['ar' => 'البريد الإلكتروني', 'en' => 'Email', 'fr' => 'E-mail']],
            ['group' => 'profile', 'key' => 'save', 'values' => ['ar' => 'حفظ', 'en' => 'Save', 'fr' => 'Enregistrer']],
            ['group' => 'profile', 'key' => 'saved', 'values' => ['ar' => 'تم الحفظ', 'en' => 'Saved', 'fr' => 'Enregistré']],
            ['group' => 'profile', 'key' => 'avatar_saved', 'values' => ['ar' => 'تم تحديث الصورة', 'en' => 'Photo updated', 'fr' => 'Photo mise à jour']],
            ['group' => 'profile', 'key' => 'avatar_removed', 'values' => ['ar' => 'تم إزالة الصورة', 'en' => 'Photo removed', 'fr' => 'Photo supprimée']],
            ['group' => 'profile', 'key' => 'email_unverified_notice', 'values' => ['ar' => 'بريدك الإلكتروني غير موثّق.', 'en' => 'Your email address is unverified.', 'fr' => 'Votre e-mail n\'est pas vérifié.']],
            ['group' => 'profile', 'key' => 'resend_verification', 'values' => ['ar' => 'إعادة إرسال رابط التحقق', 'en' => 'Resend verification link', 'fr' => 'Renvoyer le lien de vérification']],
            ['group' => 'profile', 'key' => 'verification_sent', 'values' => ['ar' => 'تم إرسال رابط التحقق', 'en' => 'Verification link sent', 'fr' => 'Lien de vérification envoyé']],
            ['group' => 'profile', 'key' => 'password_title', 'values' => ['ar' => 'تحديث كلمة المرور', 'en' => 'Update password', 'fr' => 'Mettre à jour le mot de passe']],
            ['group' => 'profile', 'key' => 'password_desc', 'values' => ['ar' => 'استخدم كلمة مرور قوية وطويلة لحماية حسابك', 'en' => 'Use a long, strong password to keep your account secure', 'fr' => 'Utilisez un mot de passe long et sécurisé']],
            ['group' => 'profile', 'key' => 'current_password', 'values' => ['ar' => 'كلمة المرور الحالية', 'en' => 'Current password', 'fr' => 'Mot de passe actuel']],
            ['group' => 'profile', 'key' => 'new_password', 'values' => ['ar' => 'كلمة المرور الجديدة', 'en' => 'New password', 'fr' => 'Nouveau mot de passe']],
            ['group' => 'profile', 'key' => 'confirm_password', 'values' => ['ar' => 'تأكيد كلمة المرور', 'en' => 'Confirm password', 'fr' => 'Confirmer le mot de passe']],
            ['group' => 'profile', 'key' => 'password_saved', 'values' => ['ar' => 'تم تحديث كلمة المرور', 'en' => 'Password updated', 'fr' => 'Mot de passe mis à jour']],
            ['group' => 'profile', 'key' => 'stats.transactions', 'values' => ['ar' => 'معاملات أنشأها', 'en' => 'Transactions created', 'fr' => 'Transactions créées']],
            ['group' => 'profile', 'key' => 'stats.documents', 'values' => ['ar' => 'وثائق رفعها', 'en' => 'Documents uploaded', 'fr' => 'Documents téléversés']],
            ['group' => 'profile', 'key' => 'stats.workflow', 'values' => ['ar' => 'إجراءات سير العمل', 'en' => 'Workflow actions', 'fr' => 'Actions de workflow']],
            ['group' => 'profile', 'key' => 'stats.audit', 'values' => ['ar' => 'سجلات النظام', 'en' => 'System logs', 'fr' => 'Journaux système']],
            ['group' => 'profile', 'key' => 'activity.login', 'values' => ['ar' => 'تسجيل دخول', 'en' => 'Login', 'fr' => 'Connexion']],
            ['group' => 'profile', 'key' => 'activity.logout', 'values' => ['ar' => 'تسجيل خروج', 'en' => 'Logout', 'fr' => 'Déconnexion']],
            ['group' => 'profile', 'key' => 'activity.profile_updated', 'values' => ['ar' => 'تحديث الملف الشخصي', 'en' => 'Profile updated', 'fr' => 'Profil mis à jour']],
            ['group' => 'profile', 'key' => 'activity.avatar_updated', 'values' => ['ar' => 'تحديث الصورة الشخصية', 'en' => 'Profile photo updated', 'fr' => 'Photo de profil mise à jour']],
            ['group' => 'profile', 'key' => 'activity.password_changed', 'values' => ['ar' => 'تغيير كلمة المرور', 'en' => 'Password changed', 'fr' => 'Mot de passe modifié']],
            ['group' => 'profile', 'key' => 'activity.transaction_created', 'values' => ['ar' => 'إنشاء معاملة', 'en' => 'Transaction created', 'fr' => 'Transaction créée']],
            ['group' => 'profile', 'key' => 'activity.document_uploaded', 'values' => ['ar' => 'رفع وثيقة', 'en' => 'Document uploaded', 'fr' => 'Document téléversé']],
            ['group' => 'profile', 'key' => 'activity.status_changed', 'values' => ['ar' => 'تغيير حالة معاملة', 'en' => 'Transaction status changed', 'fr' => 'Statut de transaction modifié']],
            ['group' => 'profile', 'key' => 'activity.login_desc', 'values' => ['ar' => 'تم تسجيل الدخول إلى النظام', 'en' => 'Signed in to the system', 'fr' => 'Connecté au système']],
            ['group' => 'profile', 'key' => 'activity.logout_desc', 'values' => ['ar' => 'تم تسجيل الخروج من النظام', 'en' => 'Signed out of the system', 'fr' => 'Déconnecté du système']],
            ['group' => 'profile', 'key' => 'activity.profile_updated_desc', 'values' => ['ar' => 'تم تحديث بيانات الحساب', 'en' => 'Account details updated', 'fr' => 'Détails du compte mis à jour']],
            ['group' => 'profile', 'key' => 'activity.avatar_updated_desc', 'values' => ['ar' => 'تم تغيير الصورة الشخصية', 'en' => 'Profile photo changed', 'fr' => 'Photo de profil modifiée']],
            ['group' => 'profile', 'key' => 'activity.password_changed_desc', 'values' => ['ar' => 'تم تغيير كلمة المرور', 'en' => 'Password was changed', 'fr' => 'Le mot de passe a été modifié']],
        ];
    }
}
