<?php

namespace App\Enums;

enum Permission: string
{
    // القائمة الرئيسية
    case DashboardView = 'dashboard.view';

    // التقارير
    case ReportsView = 'reports.view';

    // الوثائق
    case DocumentsView = 'documents.view';
    case DocumentsCreate = 'documents.create';
    case DocumentsEdit = 'documents.edit';
    case DocumentsDelete = 'documents.delete';
    case DocumentsDownload = 'documents.download';

    // الأقسام
    case DepartmentsView = 'departments.view';
    case DepartmentsCreate = 'departments.create';
    case DepartmentsEdit = 'departments.edit';
    case DepartmentsDelete = 'departments.delete';

    // التصنيفات
    case CategoriesView = 'categories.view';
    case CategoriesCreate = 'categories.create';
    case CategoriesEdit = 'categories.edit';
    case CategoriesDelete = 'categories.delete';

    // الإعدادات — عام
    case SettingsView = 'settings.view';

    // إدارة المستخدمين
    case SettingsUsersView = 'settings.users.view';
    case SettingsUsersCreate = 'settings.users.create';
    case SettingsUsersEdit = 'settings.users.edit';

    // إدارة الأدوار
    case SettingsRolesView = 'settings.roles.view';
    case SettingsRolesCreate = 'settings.roles.create';
    case SettingsRolesEdit = 'settings.roles.edit';
    case SettingsRolesDelete = 'settings.roles.delete';

    // الهيكل التنظيمي
    case SettingsOrganizationView = 'settings.organization.view';

    // أنواع المستندات
    case SettingsDocumentTypesView = 'settings.document-types.view';
    case SettingsDocumentTypesCreate = 'settings.document-types.create';
    case SettingsDocumentTypesEdit = 'settings.document-types.edit';
    case SettingsDocumentTypesDelete = 'settings.document-types.delete';

    // أنواع المعاملات
    case SettingsTransactionTypesView = 'settings.transaction-types.view';
    case SettingsTransactionTypesCreate = 'settings.transaction-types.create';
    case SettingsTransactionTypesEdit = 'settings.transaction-types.edit';
    case SettingsTransactionTypesDelete = 'settings.transaction-types.delete';

    // حالات المعاملات (سير العمل)
    case SettingsTransactionStatusesView = 'settings.transaction-statuses.view';
    case SettingsTransactionStatusesCreate = 'settings.transaction-statuses.create';
    case SettingsTransactionStatusesEdit = 'settings.transaction-statuses.edit';
    case SettingsTransactionStatusesDelete = 'settings.transaction-statuses.delete';

    // إدارة الأرشفة — المعاملات
    case TransactionsView = 'transactions.view';
    case TransactionsViewAll = 'transactions.view-all';
    case TransactionsCreate = 'transactions.create';
    case TransactionsEdit = 'transactions.edit';
    case TransactionsDelete = 'transactions.delete';
    case TransactionsStatusReview = 'transactions.status.review';
    case TransactionsStatusApprove = 'transactions.status.approve';
    case TransactionsStatusArchive = 'transactions.status.archive';
    case TransactionsReviewLogView = 'transactions.review-log.view';

    // طلبات إعارة مستندات المعاملة
    case LendingRequestsRequest = 'lending-requests.request';
    case LendingRequestsView = 'lending-requests.view';
    case LendingRequestsReview = 'lending-requests.review';
    case LendingRequestsHandover = 'lending-requests.handover';
    case LendingRequestsLogView = 'lending-requests.log.view';

    // شجرة المجلدات
    case SettingsFoldersView = 'settings.folders.view';
    case SettingsFoldersCreate = 'settings.folders.create';
    case SettingsFoldersEdit = 'settings.folders.edit';
    case SettingsFoldersDelete = 'settings.folders.delete';

    // اللغات
    case SettingsLanguagesView = 'settings.languages.view';
    case SettingsLanguagesCreate = 'settings.languages.create';
    case SettingsLanguagesEdit = 'settings.languages.edit';
    case SettingsLanguagesDelete = 'settings.languages.delete';

    // إعدادات الرقم الإشاري
    case SettingsReferenceNumbersView = 'settings.reference-numbers.view';
    case SettingsReferenceNumbersEdit = 'settings.reference-numbers.edit';

    // تجاوز تكرار الرقم الإشاري
    case DocumentsReferenceNumberDuplicateOverride = 'documents.reference-number.duplicate-override';

    // الملف الشخصي
    case ProfileView = 'profile.view';
    case ProfileEdit = 'profile.edit';
    case ProfileDelete = 'profile.delete';

    public function label(): string
    {
        return __('permissions.'.str_replace('.', '_', $this->value));
    }

    public function group(): string
    {
        return __('permissions.groups.'.$this->groupSlug());
    }

    public function groupSlug(): string
    {
        return match ($this) {
            self::DashboardView => 'main_menu',

            self::ReportsView => 'reports',

            self::DocumentsView,
            self::DocumentsCreate,
            self::DocumentsEdit,
            self::DocumentsDelete,
            self::DocumentsDownload,
            self::DocumentsReferenceNumberDuplicateOverride => 'documents',

            self::DepartmentsView,
            self::DepartmentsCreate,
            self::DepartmentsEdit,
            self::DepartmentsDelete => 'departments',

            self::CategoriesView,
            self::CategoriesCreate,
            self::CategoriesEdit,
            self::CategoriesDelete => 'categories',

            self::SettingsView => 'settings',

            self::SettingsUsersView,
            self::SettingsUsersCreate,
            self::SettingsUsersEdit => 'users_management',

            self::SettingsRolesView,
            self::SettingsRolesCreate,
            self::SettingsRolesEdit,
            self::SettingsRolesDelete => 'roles_management',

            self::SettingsOrganizationView => 'organization',

            self::SettingsDocumentTypesView,
            self::SettingsDocumentTypesCreate,
            self::SettingsDocumentTypesEdit,
            self::SettingsDocumentTypesDelete => 'document_types',

            self::SettingsTransactionTypesView,
            self::SettingsTransactionTypesCreate,
            self::SettingsTransactionTypesEdit,
            self::SettingsTransactionTypesDelete => 'transaction_types',

            self::SettingsTransactionStatusesView,
            self::SettingsTransactionStatusesCreate,
            self::SettingsTransactionStatusesEdit,
            self::SettingsTransactionStatusesDelete => 'transaction_statuses',

            self::TransactionsView,
            self::TransactionsViewAll,
            self::TransactionsCreate,
            self::TransactionsEdit,
            self::TransactionsDelete,
            self::TransactionsStatusReview,
            self::TransactionsStatusApprove,
            self::TransactionsStatusArchive,
            self::TransactionsReviewLogView => 'archive_management',

            self::LendingRequestsRequest,
            self::LendingRequestsView,
            self::LendingRequestsReview,
            self::LendingRequestsHandover,
            self::LendingRequestsLogView => 'lending_requests',

            self::SettingsFoldersView,
            self::SettingsFoldersCreate,
            self::SettingsFoldersEdit,
            self::SettingsFoldersDelete => 'folder_tree',

            self::SettingsLanguagesView,
            self::SettingsLanguagesCreate,
            self::SettingsLanguagesEdit,
            self::SettingsLanguagesDelete => 'languages',

            self::SettingsReferenceNumbersView,
            self::SettingsReferenceNumbersEdit => 'reference_numbers',

            self::ProfileView,
            self::ProfileEdit,
            self::ProfileDelete => 'profile',
        };
    }

    /** @return array<string, list<self>> */
    public static function grouped(): array
    {
        $groups = [];
        $seen = [];

        foreach (self::cases() as $permission) {
            if (isset($seen[$permission->value])) {
                continue;
            }

            $seen[$permission->value] = true;
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
