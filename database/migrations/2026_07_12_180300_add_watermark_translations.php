<?php

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationKey;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return list<array{group: string, key: string, values: array<string, string>}> */
    private function entries(): array
    {
        return [
            ['group' => 'settings', 'key' => 'watermark_title', 'values' => ['ar' => 'العلامة المائية الديناميكية', 'en' => 'Dynamic Watermark', 'fr' => 'Filigrane dynamique']],
            ['group' => 'settings', 'key' => 'watermark_desc', 'values' => ['ar' => 'التحكم في العلامة المائية وسجل تتبع العرض والتحميل والطباعة', 'en' => 'Control watermarking and view/download/print audit trail', 'fr' => 'Contrôler le filigrane et la piste d\'audit']],
            ['group' => 'settings', 'key' => 'watermark_badge', 'values' => ['ar' => 'عرض · تحميل · طباعة', 'en' => 'View · Download · Print', 'fr' => 'Affichage · Téléchargement · Impression']],

            ['group' => 'settings.watermark', 'key' => 'title', 'values' => ['ar' => 'إعدادات العلامة المائية', 'en' => 'Watermark Settings', 'fr' => 'Paramètres du filigrane']],
            ['group' => 'settings.watermark', 'key' => 'subtitle', 'values' => ['ar' => 'توليد علامة مائية لحظية عند العرض أو التحميل أو الطباعة دون تعديل الأصل', 'en' => 'Generate a live watermark on view, download, or print without changing the original', 'fr' => 'Générer un filigrane en direct sans modifier l\'original']],
            ['group' => 'settings.watermark', 'key' => 'info', 'values' => ['ar' => 'تُطبَّق العلامة على كل صفحات نسخة مؤقتة عند العرض أو التحميل أو الطباعة ببيانات المستخدم الحالي. النسخة الأصلية المخزّنة تبقى كما هي. كل عملية تحصل على رقم تتبع UUID فريد يُسجَّل في سجل التدقيق.', 'en' => 'The watermark is burned onto every page of a temporary copy on view, download, or print using the current user\'s data. The stored original stays unchanged. Each action gets a unique UUID recorded in the audit trail.', 'fr' => 'Le filigrane est appliqué sur chaque page d\'une copie temporaire à l\'affichage, au téléchargement ou à l\'impression.']],
            ['group' => 'settings.watermark', 'key' => 'general_title', 'values' => ['ar' => 'الإعدادات العامة', 'en' => 'General settings', 'fr' => 'Paramètres généraux']],
            ['group' => 'settings.watermark', 'key' => 'is_enabled', 'values' => ['ar' => 'تفعيل العلامة المائية الديناميكية', 'en' => 'Enable dynamic watermark', 'fr' => 'Activer le filigrane dynamique']],
            ['group' => 'settings.watermark', 'key' => 'opacity', 'values' => ['ar' => 'درجة الشفافية (%)', 'en' => 'Opacity (%)', 'fr' => 'Opacité (%)']],
            ['group' => 'settings.watermark', 'key' => 'opacity_desc', 'values' => ['ar' => 'من 5 إلى 60 — قيمة أقل تعني علامة أخف', 'en' => 'From 5 to 60 — lower means lighter watermark', 'fr' => 'De 5 à 60 — plus bas = plus léger']],
            ['group' => 'settings.watermark', 'key' => 'font_size', 'values' => ['ar' => 'حجم الخط', 'en' => 'Font size', 'fr' => 'Taille de police']],
            ['group' => 'settings.watermark', 'key' => 'angle', 'values' => ['ar' => 'زاوية النص المائي', 'en' => 'Watermark angle', 'fr' => 'Angle du filigrane']],
            ['group' => 'settings.watermark', 'key' => 'angle_desc', 'values' => ['ar' => 'بالدرجات، مثلاً -45 للنص المائل', 'en' => 'In degrees, e.g. -45 for diagonal text', 'fr' => 'En degrés, ex. -45']],
            ['group' => 'settings.watermark', 'key' => 'elements_title', 'values' => ['ar' => 'عناصر العلامة المائية', 'en' => 'Watermark elements', 'fr' => 'Éléments du filigrane']],
            ['group' => 'settings.watermark', 'key' => 'show_center_text', 'values' => ['ar' => 'نص مائي في منتصف الصفحة', 'en' => 'Center watermark text', 'fr' => 'Texte central']],
            ['group' => 'settings.watermark', 'key' => 'show_footer', 'values' => ['ar' => 'سطر التتبع أسفل الصفحة', 'en' => 'Footer tracking line', 'fr' => 'Ligne de suivi en bas']],
            ['group' => 'settings.watermark', 'key' => 'show_qr_code', 'values' => ['ar' => 'رمز QR أسفل يمين الصفحة', 'en' => 'QR code (bottom-right)', 'fr' => 'Code QR (bas droite)']],
            ['group' => 'settings.watermark', 'key' => 'fields_title', 'values' => ['ar' => 'البيانات الظاهرة داخل العلامة', 'en' => 'Fields shown in watermark', 'fr' => 'Champs affichés']],
            ['group' => 'settings.watermark', 'key' => 'show_user_name', 'values' => ['ar' => 'اسم المستخدم', 'en' => 'User name', 'fr' => 'Nom d\'utilisateur']],
            ['group' => 'settings.watermark', 'key' => 'show_user_id', 'values' => ['ar' => 'الرقم الوظيفي / معرف المستخدم', 'en' => 'User ID', 'fr' => 'ID utilisateur']],
            ['group' => 'settings.watermark', 'key' => 'show_department', 'values' => ['ar' => 'الإدارة', 'en' => 'Department', 'fr' => 'Département']],
            ['group' => 'settings.watermark', 'key' => 'show_datetime', 'values' => ['ar' => 'تاريخ ووقت العملية', 'en' => 'Date & time', 'fr' => 'Date et heure']],
            ['group' => 'settings.watermark', 'key' => 'show_action_type', 'values' => ['ar' => 'نوع العملية (View / Download / Print)', 'en' => 'Action type (View / Download / Print)', 'fr' => 'Type d\'action']],
            ['group' => 'settings.watermark', 'key' => 'show_transaction_id', 'values' => ['ar' => 'رقم التتبع (Transaction ID)', 'en' => 'Transaction ID', 'fr' => 'Transaction ID']],
            ['group' => 'settings.watermark', 'key' => 'apply_title', 'values' => ['ar' => 'تطبيق العلامة عند', 'en' => 'Apply watermark on', 'fr' => 'Appliquer sur']],
            ['group' => 'settings.watermark', 'key' => 'apply_on_view', 'values' => ['ar' => 'العرض', 'en' => 'View', 'fr' => 'Affichage']],
            ['group' => 'settings.watermark', 'key' => 'apply_on_download', 'values' => ['ar' => 'التحميل', 'en' => 'Download', 'fr' => 'Téléchargement']],
            ['group' => 'settings.watermark', 'key' => 'apply_on_print', 'values' => ['ar' => 'الطباعة', 'en' => 'Print', 'fr' => 'Impression']],
            ['group' => 'settings.watermark', 'key' => 'save', 'values' => ['ar' => 'حفظ الإعدادات', 'en' => 'Save settings', 'fr' => 'Enregistrer']],
            ['group' => 'settings.watermark', 'key' => 'readonly', 'values' => ['ar' => 'لديك صلاحية العرض فقط — لا يمكنك تعديل هذه الإعدادات.', 'en' => 'View-only access — you cannot edit these settings.', 'fr' => 'Lecture seule.']],
            ['group' => 'settings.watermark', 'key' => 'lookup_title', 'values' => ['ar' => 'البحث برقم التتبع (Transaction ID)', 'en' => 'Lookup by Transaction ID', 'fr' => 'Recherche par Transaction ID']],
            ['group' => 'settings.watermark', 'key' => 'transaction_id', 'values' => ['ar' => 'رقم التتبع', 'en' => 'Transaction ID', 'fr' => 'Transaction ID']],
            ['group' => 'settings.watermark', 'key' => 'lookup_search', 'values' => ['ar' => 'بحث', 'en' => 'Search', 'fr' => 'Rechercher']],
            ['group' => 'settings.watermark', 'key' => 'lookup_not_found', 'values' => ['ar' => 'لم يتم العثور على سجل بهذا الرقم.', 'en' => 'No audit record found for this ID.', 'fr' => 'Aucun enregistrement trouvé.']],
            ['group' => 'settings.watermark', 'key' => 'audit_user', 'values' => ['ar' => 'المستخدم', 'en' => 'User', 'fr' => 'Utilisateur']],
            ['group' => 'settings.watermark', 'key' => 'audit_department', 'values' => ['ar' => 'الإدارة', 'en' => 'Department', 'fr' => 'Département']],
            ['group' => 'settings.watermark', 'key' => 'audit_document', 'values' => ['ar' => 'المستند', 'en' => 'Document', 'fr' => 'Document']],
            ['group' => 'settings.watermark', 'key' => 'audit_version', 'values' => ['ar' => 'إصدار المستند', 'en' => 'Document version', 'fr' => 'Version']],
            ['group' => 'settings.watermark', 'key' => 'audit_action', 'values' => ['ar' => 'نوع العملية', 'en' => 'Action type', 'fr' => 'Action']],
            ['group' => 'settings.watermark', 'key' => 'audit_status', 'values' => ['ar' => 'الحالة', 'en' => 'Status', 'fr' => 'Statut']],
            ['group' => 'settings.watermark', 'key' => 'audit_watermark', 'values' => ['ar' => 'تم تطبيق العلامة', 'en' => 'Watermark applied', 'fr' => 'Filigrane appliqué']],
            ['group' => 'settings.watermark', 'key' => 'audit_ip', 'values' => ['ar' => 'عنوان IP', 'en' => 'IP address', 'fr' => 'Adresse IP']],
            ['group' => 'settings.watermark', 'key' => 'audit_ua', 'values' => ['ar' => 'المتصفح / الجهاز', 'en' => 'Browser / device', 'fr' => 'Navigateur / appareil']],
            ['group' => 'settings.watermark', 'key' => 'audit_session', 'values' => ['ar' => 'معرّف الجلسة', 'en' => 'Session ID', 'fr' => 'ID de session']],
            ['group' => 'settings.watermark', 'key' => 'audit_at', 'values' => ['ar' => 'التاريخ والوقت', 'en' => 'Date & time', 'fr' => 'Date et heure']],

            ['group' => 'documents', 'key' => 'print', 'values' => ['ar' => 'طباعة', 'en' => 'Print', 'fr' => 'Imprimer']],
            ['group' => 'documents', 'key' => 'print_title', 'values' => ['ar' => 'طباعة المستند', 'en' => 'Print document', 'fr' => 'Imprimer le document']],
            ['group' => 'documents', 'key' => 'print_now', 'values' => ['ar' => 'طباعة الآن', 'en' => 'Print now', 'fr' => 'Imprimer maintenant']],

            ['group' => 'common', 'key' => 'yes', 'values' => ['ar' => 'نعم', 'en' => 'Yes', 'fr' => 'Oui']],
            ['group' => 'common', 'key' => 'no', 'values' => ['ar' => 'لا', 'en' => 'No', 'fr' => 'Non']],
            ['group' => 'common', 'key' => 'close', 'values' => ['ar' => 'إغلاق', 'en' => 'Close', 'fr' => 'Fermer']],

            ['group' => 'messages', 'key' => 'watermark.saved', 'values' => ['ar' => 'تم حفظ إعدادات العلامة المائية بنجاح.', 'en' => 'Watermark settings saved successfully.', 'fr' => 'Paramètres du filigrane enregistrés.']],
            ['group' => 'messages', 'key' => 'watermark.failed', 'values' => ['ar' => 'تعذّر توليد النسخة الموسومة بالعلامة المائية.', 'en' => 'Failed to generate the watermarked copy.', 'fr' => 'Échec de génération du filigrane.']],

            ['group' => 'permissions', 'key' => 'settings_watermark_view', 'values' => ['ar' => 'عرض إعدادات العلامة المائية', 'en' => 'View watermark settings', 'fr' => 'Voir les paramètres du filigrane']],
            ['group' => 'permissions', 'key' => 'settings_watermark_edit', 'values' => ['ar' => 'تعديل إعدادات العلامة المائية', 'en' => 'Edit watermark settings', 'fr' => 'Modifier les paramètres du filigrane']],
            ['group' => 'permissions', 'key' => 'groups.watermark', 'values' => ['ar' => 'إعدادات العلامة المائية', 'en' => 'Watermark settings', 'fr' => 'Paramètres du filigrane']],
        ];
    }

    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('languages')
            || ! \Illuminate\Support\Facades\Schema::hasTable('translation_keys')
            || ! \Illuminate\Support\Facades\Schema::hasTable('translations')) {
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
        if (! Schema::hasTable('translation_keys') || ! Schema::hasTable('translations')) {
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
