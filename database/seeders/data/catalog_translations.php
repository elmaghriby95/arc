<?php

/** Supplemental [ar, en, fr] translations keyed as group.subkey */
$catalogTranslations = [];

$add = static function (string $k, string $ar, string $en, string $fr) use (&$catalogTranslations): void {
    $catalogTranslations[$k] = [$ar, $en, $fr];
};

// ========== NAV (from TranslationSeeder) ==========
foreach ([
    'nav.tagline' => ['إدارة الوثائق والأرشفة', 'Document & Archive Management', 'Gestion des documents et archives'],
    'nav.dashboard' => ['لوحة التحكم', 'Dashboard', 'Tableau de bord'],
    'nav.documents' => ['الوثائق', 'Documents', 'Documents'],
    'nav.transactions' => ['إدارة الأرشفة', 'Archive Management', 'Gestion des archives'],
    'nav.reports' => ['التقارير', 'Reports', 'Rapports'],
    'nav.departments' => ['الأقسام', 'Departments', 'Départements'],
    'nav.categories' => ['التصنيفات', 'Categories', 'Catégories'],
    'nav.settings' => ['الإعدادات', 'Settings', 'Paramètres'],
    'nav.notifications' => ['الإشعارات', 'Notifications', 'Notifications'],
    'nav.unread_count' => [':count غير مقروء', ':count unread', ':count non lu(s)'],
    'nav.no_new_notifications' => ['لا إشعارات جديدة', 'No new notifications', 'Aucune nouvelle notification'],
    'nav.mark_all_read' => ['تعليم الكل', 'Mark all read', 'Tout marquer comme lu'],
    'nav.no_notifications' => ['لا توجد إشعارات', 'No notifications', 'Aucune notification'],
    'nav.notifications_empty_hint' => ['ستظهر هنا تحديثات حالة المعاملات والأرشفة فور حدوثها.', 'Transaction and archive status updates will appear here.', 'Les mises à jour apparaîtront ici.'],
    'nav.last_notifications' => ['آخر :count إشعار', 'Last :count notifications', 'Dernières :count notifications'],
    'nav.profile' => ['الملف الشخصي', 'Profile', 'Profil'],
    'nav.logout' => ['تسجيل الخروج', 'Log Out', 'Déconnexion'],
    'nav.menu' => ['القائمة', 'Menu', 'Menu'],
    'nav.language' => ['اللغة', 'Language', 'Langue'],
] as $k => $t) {
    $add($k, $t[0], $t[1], $t[2]);
}

// ========== AUTH ==========
foreach ([
    'auth.login' => ['تسجيل الدخول', 'Log in', 'Connexion'],
    'auth.register' => ['إنشاء حساب', 'Register', 'Inscription'],
    'auth.email' => ['البريد الإلكتروني', 'Email', 'E-mail'],
    'auth.password' => ['كلمة المرور', 'Password', 'Mot de passe'],
    'auth.remember_me' => ['تذكرني', 'Remember me', 'Se souvenir de moi'],
    'auth.forgot_password' => ['نسيت كلمة المرور؟', 'Forgot your password?', 'Mot de passe oublié ?'],
    'auth.name' => ['الاسم', 'Name', 'Nom'],
    'auth.confirm_password' => ['تأكيد كلمة المرور', 'Confirm Password', 'Confirmer le mot de passe'],
    'auth.already_registered' => ['لديك حساب بالفعل؟', 'Already registered?', 'Déjà inscrit ?'],
    'auth.logged_in' => ['تم تسجيل الدخول بنجاح!', "You're logged in!", 'Vous êtes connecté !'],
    'auth.welcome_back' => ['مرحباً بعودتك', 'Welcome back', 'Bon retour'],
    'auth.login_desc' => ['سجّل دخولك للوصول إلى لوحة التحكم وإدارة الأرشيف', 'Sign in to access the dashboard and archive', 'Connectez-vous pour accéder au tableau de bord'],
    'auth.no_account' => ['ليس لديك حساب؟', "Don't have an account?", 'Pas de compte ?'],
    'auth.show_password' => ['إظهار كلمة المرور', 'Show password', 'Afficher le mot de passe'],
    'auth.login_page_title' => ['تسجيل الدخول', 'Log in', 'Connexion'],
    'auth.brand_subtitle' => ['منصة متكاملة لإدارة وأرشفة الوثائق الإلكترونية بأمان وكفاءة', 'Integrated platform for secure document archiving', "Plateforme intégrée pour l'archivage sécurisé"],
    'auth.feature_1' => ['أرشفة مركزية للوثائق والمراسلات', 'Central archiving for documents and correspondence', 'Archivage central des documents'],
    'auth.feature_2' => ['بحث سريع وتصنيف ذكي', 'Fast search and smart classification', 'Recherche rapide et classification'],
    'auth.feature_3' => ['تتبع الإصدارات وسجل العمليات', 'Version tracking and audit log', "Suivi des versions et journal d'audit"],
    'auth.forgot_password_intro' => ['نسيت كلمة المرور؟ لا مشكلة. أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة تعيين كلمة المرور.', 'Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.', 'Mot de passe oublié ? Indiquez votre e-mail et nous vous enverrons un lien de réinitialisation.'],
    'auth.email_password_reset_link' => ['إرسال رابط إعادة تعيين كلمة المرور', 'Email Password Reset Link', 'Envoyer le lien de réinitialisation'],
    'auth.reset_password' => ['إعادة تعيين كلمة المرور', 'Reset Password', 'Réinitialiser le mot de passe'],
] as $k => $t) {
    $add($k, $t[0], $t[1], $t[2]);
}

// ========== COMMON ==========
foreach ([
    'common.save' => ['حفظ', 'Save', 'Enregistrer'],
    'common.cancel' => ['إلغاء', 'Cancel', 'Annuler'],
    'common.delete' => ['حذف', 'Delete', 'Supprimer'],
    'common.edit' => ['تعديل', 'Edit', 'Modifier'],
    'common.add' => ['إضافة', 'Add', 'Ajouter'],
    'common.back' => ['← العودة للإعدادات', '← Back to settings', '← Retour aux paramètres'],
    'common.view_all' => ['عرض الكل', 'View all', 'Tout voir'],
    'common.confirm_delete' => ['هل أنت متأكد؟', 'Are you sure?', 'Êtes-vous sûr ?'],
    'common.active' => ['نشطة', 'Active', 'Active'],
    'common.inactive' => ['غير نشطة', 'Inactive', 'Inactive'],
    'common.default' => ['افتراضية', 'Default', 'Par défaut'],
    'common.yes' => ['نعم', 'Yes', 'Oui'],
    'common.no' => ['لا', 'No', 'Non'],
    'common.search' => ['بحث', 'Search', 'Rechercher'],
    'common.actions' => ['إجراءات', 'Actions', 'Actions'],
    'common.status' => ['الحالة', 'Status', 'Statut'],
    'common.date' => ['التاريخ', 'Date', 'Date'],
    'common.description' => ['الوصف', 'Description', 'Description'],
    'common.name' => ['الاسم', 'Name', 'Nom'],
    'common.email' => ['البريد الإلكتروني', 'Email', 'E-mail'],
    'common.role' => ['الدور', 'Role', 'Rôle'],
    'common.department' => ['القسم', 'Department', 'Département'],
    'common.direction_rtl' => ['من اليمين لليسار (RTL)', 'Right to left (RTL)', 'Droite à gauche (RTL)'],
    'common.direction_ltr' => ['من اليسار لليمين (LTR)', 'Left to right (LTR)', 'Gauche à droite (LTR)'],
    'common.direction_rtl_short' => ['من اليمين لليسار', 'Right to left', 'Droite à gauche'],
    'common.direction_ltr_short' => ['من اليسار لليمين', 'Left to right', 'Gauche à droite'],
    'common.org_unit' => ['الوحدة التنظيمية', 'Organizational unit', 'Unité organisationnelle'],
    'common.folder' => ['المجلد', 'Folder', 'Dossier'],
    'common.transaction_type' => ['نوع المعاملة', 'Transaction type', 'Type de transaction'],
    'common.all' => ['الكل', 'All', 'Tout'],
    'common.all_dash' => ['— الكل —', '— All —', '— Tout —'],
    'common.filter' => ['تصفية', 'Filter', 'Filtrer'],
    'common.reference_number' => ['الرقم الإشاري', 'Reference number', 'Numéro de référence'],
    'common.title' => ['العنوان', 'Title', 'Titre'],
    'common.view' => ['عرض', 'View', 'Voir'],
    'common.download' => ['تحميل', 'Download', 'Télécharger'],
    'common.back_to_transactions' => ['← العودة للمعاملات', '← Back to transactions', '← Retour aux transactions'],
    'common.back_to_transaction' => ['← العودة للمعاملة', '← Back to transaction', '← Retour à la transaction'],
    'common.back_to_documents' => ['← العودة للوثائق', '← Back to documents', '← Retour aux documents'],
    'common.back_to_users' => ['← العودة للمستخدمين', '← Back to users', '← Retour aux utilisateurs'],
    'common.back_to_roles' => ['← العودة للأدوار', '← Back to roles', '← Retour aux rôles'],
    'common.notes' => ['الملاحظات', 'Notes', 'Notes'],
    'common.next' => ['التالي', 'Next', 'Suivant'],
    'common.previous' => ['السابق', 'Previous', 'Précédent'],
    'common.or' => ['أو', 'or', 'ou'],
    'common.optional_dash' => ['— اختياري —', '— Optional —', '— Optionnel —'],
    'common.choose_org_unit' => ['— اختر الوحدة التنظيمية —', '— Choose organizational unit —', '— Choisir une unité —'],
    'common.choose_folder' => ['— اختر مجلد —', '— Choose folder —', '— Choisir un dossier —'],
    'common.search_placeholder' => ['بحث...', 'Search...', 'Rechercher...'],
    'common.reset' => ['إعادة تعيين', 'Reset', 'Réinitialiser'],
    'common.update' => ['تحديث', 'Update', 'Mettre à jour'],
    'common.total' => ['الإجمالي', 'Total', 'Total'],
    'common.expand_all' => ['توسيع الكل', 'Expand all', 'Tout développer'],
] as $k => $t) {
    $add($k, $t[0], $t[1], $t[2]);
}

// messages seeder extras
foreach ([
    'messages.language_added' => ['تم إضافة اللغة بنجاح.', 'Language added successfully.', 'Langue ajoutée avec succès.'],
    'messages.language_updated' => ['تم تحديث اللغة بنجاح.', 'Language updated successfully.', 'Langue mise à jour avec succès.'],
    'messages.language_deleted' => ['تم حذف اللغة بنجاح.', 'Language deleted successfully.', 'Langue supprimée avec succès.'],
    'messages.cannot_delete_default_language' => ['لا يمكن حذف اللغة الافتراضية.', 'Cannot delete the default language.', 'Impossible de supprimer la langue par défaut.'],
    'messages.translations_saved' => ['تم حفظ الترجمات بنجاح.', 'Translations saved successfully.', 'Traductions enregistrées avec succès.'],
    'messages.translation_key_saved' => ['تم إضافة الكلمة بنجاح.', 'Translation key saved successfully.', 'Clé de traduction enregistrée.'],
    'messages.translation_key_deleted' => ['تم حذف الكلمة بنجاح.', 'Translation key deleted successfully.', 'Clé de traduction supprimée.'],
    'messages.locale_switched' => ['تم تغيير اللغة.', 'Language changed.', 'Langue modifiée.'],
    'messages.no_access' => ['لا تملك صلاحية الوصول إلى أي صفحة في النظام. تواصل مع مدير النظام.', 'You do not have access to any page. Contact your administrator.', "Vous n'avez accès à aucune page. Contactez l'administrateur."],
    'messages.reference_number.operational_disabled' => ['الرقم التشغيلي غير مفعّل في إعدادات النظام.', 'Operational number is not enabled in system settings.', "Le numéro opérationnel n'est pas activé."],
    'messages.reference_number.required' => ['يجب إدخال الرقم الإشاري لكل مستند أو اختيار الرقم التشغيلي.', 'Reference number or operational number is required for each document.', 'Un numéro de référence ou opérationnel est requis.'],
    'messages.reference_number.format_denied' => ['صيغة الرقم الإشاري غير مسموحة حسب إعدادات النظام.', 'Reference number format is not allowed by system settings.', 'Format de numéro de référence non autorisé.'],
    'messages.reference_number.month_required' => ['الشهر مطلوب في الرقم الإشاري حسب إعدادات النظام.', 'Month is required in the reference number per system settings.', 'Le mois est requis dans le numéro de référence.'],
    'messages.reference_number.original_required' => ['رقم المستند الأصلي مطلوب حسب إعدادات النظام.', 'Original document number is required per system settings.', 'Le numéro du document original est requis.'],
    'messages.reference_number.duplicate' => ['الرقم الإشاري «:number» مستخدم مسبقاً ضمن نفس الوحدة والسنة.', 'Reference number ":number" is already used in the same unit and year.', 'Le numéro «:number» est déjà utilisé.'],
] as $k => $t) {
    $add($k, $t[0], $t[1], $t[2]);
}

// Continue in part 2 - settings, dashboard, etc.
require __DIR__ . '/catalog_translations_part2.php';
