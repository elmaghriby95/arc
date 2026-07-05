<?php

namespace App\Enums;

enum ReportType: string
{
    case TransactionPipeline = 'transaction-pipeline';
    case StaleTransactions = 'stale-transactions';
    case DepartmentProductivity = 'department-productivity';
    case DocumentsAttachments = 'documents-attachments';
    case StatusHistory = 'status-history';
    case CompletionTime = 'completion-time';
    case UserActivity = 'user-activity';
    case ReferenceNumbers = 'reference-numbers';
    case FolderDistribution = 'folder-distribution';
    case ConfidentialDocuments = 'confidential-documents';

    public function label(): string
    {
        return __('reports.'.$this->translationKey().'.label');
    }

    public function description(): string
    {
        return __('reports.'.$this->translationKey().'.description');
    }

    public function phase(): int
    {
        return match ($this) {
            self::TransactionPipeline,
            self::StaleTransactions,
            self::DepartmentProductivity,
            self::DocumentsAttachments,
            self::StatusHistory => 1,
            default => 2,
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
            self::CompletionTime => 'reports-module--rose',
            self::UserActivity => 'reports-module--slate',
            self::ReferenceNumbers => 'reports-module--orange',
            self::FolderDistribution => 'reports-module--teal',
            self::ConfidentialDocuments => 'reports-module--rose',
        };
    }

    public function permission(): string
    {
        return 'reports.'.$this->value.'.view';
    }

    public function permissionLabel(): string
    {
        return __('reports.permission.'.$this->translationKey());
    }

    /** @return list<self> */
    public static function accessibleFor(?\App\Models\User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $type) => $user->canViewReport($type)
        ));
    }

    /** @return list<self> */
    public static function accessibleForPhase(?\App\Models\User $user, int $phase): array
    {
        return array_values(array_filter(
            self::accessibleFor($user),
            fn (self $type) => $type->phase() === $phase
        ));
    }

    /** @return list<string> */
    public static function permissionValues(): array
    {
        return array_map(fn (self $type) => $type->permission(), self::cases());
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

    /** @return list<self> */
    public static function forPhase(int $phase): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type) => $type->phase() === $phase
        ));
    }

    private function translationKey(): string
    {
        return str_replace('-', '_', $this->value);
    }
}
