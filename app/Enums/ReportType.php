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
        return __('reports.'.$this->translationKey().'.label');
    }

    public function description(): string
    {
        return __('reports.'.$this->translationKey().'.description');
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

    private function translationKey(): string
    {
        return str_replace('-', '_', $this->value);
    }
}
