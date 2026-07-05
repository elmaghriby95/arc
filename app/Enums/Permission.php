<?php

namespace App\Enums;

enum Permission: string
{
    // القائمة الرئيسية
    case DashboardView = 'dashboard.view';

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
        return match ($this) {
            self::DashboardView => 'عرض لوحة التحكم',

            self::DocumentsView => 'عرض الوثائق (مستندات المعاملات)',
            self::DocumentsCreate => 'إضافة وثيقة',
            self::DocumentsEdit => 'تعديل وثيقة',
            self::DocumentsDelete => 'حذف وثيقة',
            self::DocumentsDownload => 'تحميل وثيقة',

            self::DepartmentsView => 'عرض الأقسام',
            self::DepartmentsCreate => 'إضافة قسم',
            self::DepartmentsEdit => 'تعديل قسم',
            self::DepartmentsDelete => 'حذف قسم',

            self::CategoriesView => 'عرض التصنيفات',
            self::CategoriesCreate => 'إضافة تصنيف',
            self::CategoriesEdit => 'تعديل تصنيف',
            self::CategoriesDelete => 'حذف تصنيف',

            self::SettingsView => 'عرض صفحة الإعدادات',

            self::SettingsUsersView => 'عرض المستخدمين',
            self::SettingsUsersCreate => 'إنشاء مستخدم',
            self::SettingsUsersEdit => 'تعديل المستخدمين',

            self::SettingsRolesView => 'عرض الأدوار',
            self::SettingsRolesCreate => 'إنشاء دور',
            self::SettingsRolesEdit => 'تعديل دور',
            self::SettingsRolesDelete => 'حذف دور',

            self::SettingsOrganizationView => 'عرض الهيكل التنظيمي',

            self::SettingsDocumentTypesView => 'عرض أنواع المستندات',
            self::SettingsDocumentTypesCreate => 'إضافة نوع مستند',
            self::SettingsDocumentTypesEdit => 'تعديل نوع مستند',
            self::SettingsDocumentTypesDelete => 'حذف نوع مستند',

            self::SettingsTransactionTypesView => 'عرض أنواع المعاملات',
            self::SettingsTransactionTypesCreate => 'إضافة نوع معاملة',
            self::SettingsTransactionTypesEdit => 'تعديل نوع معاملة',
            self::SettingsTransactionTypesDelete => 'حذف نوع معاملة',

            self::SettingsTransactionStatusesView => 'عرض حالات المعاملات',
            self::SettingsTransactionStatusesCreate => 'إضافة حالة معاملة',
            self::SettingsTransactionStatusesEdit => 'تعديل حالة معاملة',
            self::SettingsTransactionStatusesDelete => 'حذف حالة معاملة',

            self::TransactionsView => 'عرض المعاملات',
            self::TransactionsViewAll => 'عرض كل المعاملات',
            self::TransactionsCreate => 'إنشاء معاملة',
            self::TransactionsEdit => 'تعديل معاملة',
            self::TransactionsDelete => 'حذف معاملة',
            self::TransactionsStatusReview => 'الانتقال إلى قيد المراجعة',
            self::TransactionsStatusApprove => 'اعتماد المعاملة',
            self::TransactionsStatusArchive => 'أرشفة المعاملة',

            self::SettingsFoldersView => 'عرض شجرة المجلدات',
            self::SettingsFoldersCreate => 'إضافة مجلد',
            self::SettingsFoldersEdit => 'تعديل مجلد',
            self::SettingsFoldersDelete => 'حذف مجلد',

            self::SettingsLanguagesView => 'عرض اللغات',
            self::SettingsLanguagesCreate => 'إضافة لغة',
            self::SettingsLanguagesEdit => 'تعديل لغة',
            self::SettingsLanguagesDelete => 'حذف لغة',

            self::SettingsReferenceNumbersView => 'عرض إعدادات الرقم الإشاري',
            self::SettingsReferenceNumbersEdit => 'تعديل إعدادات الرقم الإشاري',

            self::DocumentsReferenceNumberDuplicateOverride => 'السماح بتكرار الرقم الإشاري',

            self::ProfileView => 'عرض الملف الشخصي',
            self::ProfileEdit => 'تعديل الملف الشخصي',
            self::ProfileDelete => 'حذف الحساب',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DashboardView => 'القائمة الرئيسية',

            self::DocumentsView,
            self::DocumentsCreate,
            self::DocumentsEdit,
            self::DocumentsDelete,
            self::DocumentsDownload => 'الوثائق',

            self::DepartmentsView,
            self::DepartmentsCreate,
            self::DepartmentsEdit,
            self::DepartmentsDelete => 'الأقسام',

            self::CategoriesView,
            self::CategoriesCreate,
            self::CategoriesEdit,
            self::CategoriesDelete => 'التصنيفات',

            self::SettingsView => 'الإعدادات',

            self::SettingsUsersView,
            self::SettingsUsersCreate,
            self::SettingsUsersEdit => 'إدارة المستخدمين',

            self::SettingsRolesView,
            self::SettingsRolesCreate,
            self::SettingsRolesEdit,
            self::SettingsRolesDelete => 'إدارة الأدوار',

            self::SettingsOrganizationView => 'الهيكل التنظيمي',

            self::SettingsDocumentTypesView,
            self::SettingsDocumentTypesCreate,
            self::SettingsDocumentTypesEdit,
            self::SettingsDocumentTypesDelete => 'أنواع المستندات',

            self::SettingsTransactionTypesView,
            self::SettingsTransactionTypesCreate,
            self::SettingsTransactionTypesEdit,
            self::SettingsTransactionTypesDelete => 'أنواع المعاملات',

            self::SettingsTransactionStatusesView,
            self::SettingsTransactionStatusesCreate,
            self::SettingsTransactionStatusesEdit,
            self::SettingsTransactionStatusesDelete => 'حالات المعاملات',

            self::TransactionsView,
            self::TransactionsViewAll,
            self::TransactionsCreate,
            self::TransactionsEdit,
            self::TransactionsDelete,
            self::TransactionsStatusReview,
            self::TransactionsStatusApprove,
            self::TransactionsStatusArchive => 'إدارة الأرشفة',

            self::SettingsFoldersView,
            self::SettingsFoldersCreate,
            self::SettingsFoldersEdit,
            self::SettingsFoldersDelete => 'شجرة المجلدات',

            self::SettingsLanguagesView,
            self::SettingsLanguagesCreate,
            self::SettingsLanguagesEdit,
            self::SettingsLanguagesDelete => 'اللغات',

            self::SettingsReferenceNumbersView,
            self::SettingsReferenceNumbersEdit => 'إعدادات الرقم الإشاري',

            self::DocumentsReferenceNumberDuplicateOverride => 'الوثائق',

            self::ProfileView,
            self::ProfileEdit,
            self::ProfileDelete => 'الملف الشخصي',
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
