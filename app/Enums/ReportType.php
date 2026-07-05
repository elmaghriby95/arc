<?php

namespace App\Enums;

enum ReportType: string
{
    case TransactionPipeline = 'transaction-pipeline';
    case StaleTransactions = 'stale-transactions';
    case DepartmentProductivity = 'department-productivity';
    case DocumentsAttachments = 'documents-attachments';
    case StatusHistory = 'status-history';

    public function label(): string
    {
        return match ($this) {
            self::TransactionPipeline => 'حالة المعاملات',
            self::StaleTransactions => 'المعاملات المتأخرة',
            self::DepartmentProductivity => 'إنتاجية الأقسام',
            self::DocumentsAttachments => 'الوثائق والمرفقات',
            self::StatusHistory => 'سجل حركة المعاملات',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TransactionPipeline => 'توزيع المعاملات حسب مراحل سير العمل والأقسام وأنواع المعاملات',
            self::StaleTransactions => 'المعاملات غير المكتملة التي تجاوزت مدة التوقف المحددة',
            self::DepartmentProductivity => 'مقارنة أداء الأقسام في إنشاء وإنجاز المعاملات',
            self::DocumentsAttachments => 'إحصائيات المرفقات وحجم التخزين وتوزيع أنواع الملفات',
            self::StatusHistory => 'سجل تفصيلي لجميع انتقالات حالات المعاملات',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::TransactionPipeline => 'reports-module--indigo',
            self::StaleTransactions => 'reports-module--amber',
            self::DepartmentProductivity => 'reports-module--cyan',
            self::DocumentsAttachments => 'reports-module--violet',
            self::StatusHistory => 'reports-module--emerald',
        };
    }

    public function view(): string
    {
        return 'reports.'.$this->value;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.'.$this->value;
    }

    public static function tryFromRoute(?string $slug): ?self
    {
        return $slug ? self::tryFrom($slug) : null;
    }
}
