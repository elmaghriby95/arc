<?php

namespace App\Exports;

use App\Enums\ReportType;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportWorkbookExport implements WithMultipleSheets
{
    /** @param list<ReportSheetExport> $sheets */
    public function __construct(
        private readonly array $sheets,
    ) {}

    /** @return list<ReportSheetExport> */
    public function sheets(): array
    {
        return $this->sheets;
    }

    /** @param array<string, mixed> $data */
    public static function forType(ReportType $type, array $data): self
    {
        return match ($type) {
            ReportType::TransactionPipeline => self::pipeline($data),
            ReportType::StaleTransactions => self::stale($data),
            ReportType::DepartmentProductivity => self::productivity($data),
            ReportType::DocumentsAttachments => self::documents($data),
            ReportType::StatusHistory => self::history($data),
            ReportType::CompletionTime => self::completionTime($data),
            ReportType::UserActivity => self::userActivity($data),
            ReportType::ReferenceNumbers => self::referenceNumbers($data),
            ReportType::FolderDistribution => self::folderDistribution($data),
            ReportType::ConfidentialDocuments => self::confidentialDocuments($data),
            ReportType::SystemOperations => self::systemOperations($data),
        };
    }

    /** @param array<string, mixed> $data */
    private static function pipeline(array $data): self
    {
        $summaryRows = collect($data['summary'])->map(fn ($row) => [
            $row['name'],
            $row['code'],
            $row['count'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['type'],
            $row['status'],
            $row['creator'],
            $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.status_summary'), [
                __('common.status'),
                __('reports.export.col.code'),
                __('reports.count'),
            ], $summaryRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.reference_number'),
                __('common.title'),
                __('common.department'),
                __('common.transaction_type'),
                __('common.status'),
                __('reports.export.col.creator'),
                __('common.date'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function stale(array $data): self
    {
        $deptRows = collect($data['by_department'])->map(fn ($row) => [
            $row['department'],
            $row['count'],
            $row['avg_days'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['type'],
            $row['status'],
            $row['days_stale'],
            $row['creator'],
            $row['last_activity'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_department'), [
                __('common.department'),
                __('reports.count'),
                __('reports.avg_days'),
            ], $deptRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.reference_number'),
                __('common.title'),
                __('common.department'),
                __('common.transaction_type'),
                __('common.status'),
                __('reports.export.col.stale_days'),
                __('reports.export.col.creator'),
                __('reports.last_activity'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function productivity(array $data): self
    {
        $rows = collect($data['details'])->map(fn ($row) => [
            $row['department'],
            $row['created'],
            $row['draft'],
            $row['in_progress'],
            $row['archived'],
            $row['completed_in_period'],
            $row['completion_rate'].'%',
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.productivity'), [
                __('common.department'),
                __('reports.created'),
                __('reports.draft'),
                __('reports.in_progress'),
                __('reports.archived'),
                __('reports.completed_in_period'),
                __('reports.completion_rate'),
            ], $rows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function documents(array $data): self
    {
        $kindRows = collect($data['by_kind'])->map(fn ($row) => [
            $row['label'],
            $row['count'],
            $row['size_formatted'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['title'],
            $row['transaction'],
            $row['department'],
            $row['type'],
            $row['kind'],
            $row['mime'],
            $row['size'],
            $row['uploader'],
            $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_kind'), [
                __('reports.export.col.file_kind'),
                __('reports.count'),
                __('reports.size'),
            ], $kindRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.title'),
                __('dashboard.transaction'),
                __('common.department'),
                __('common.transaction_type'),
                __('reports.export.col.file_kind'),
                __('reports.export.col.mime'),
                __('reports.size'),
                __('reports.uploader'),
                __('common.date'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function history(array $data): self
    {
        $actionRows = collect($data['by_action'])->map(fn ($row) => [
            $row['label'],
            $row['count'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['date'],
            $row['reference_number'],
            $row['title'],
            $row['department'],
            $row['from_status'],
            $row['to_status'],
            $row['action'],
            $row['changed_by'],
            $row['notes'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_action'), [
                __('reports.export.col.action'),
                __('reports.count'),
            ], $actionRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.date'),
                __('common.reference_number'),
                __('common.title'),
                __('common.department'),
                __('reports.export.col.from_status'),
                __('reports.export.col.to_status'),
                __('reports.export.col.action'),
                __('reports.export.col.changed_by'),
                __('reports.export.col.notes'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function completionTime(array $data): self
    {
        $deptRows = collect($data['by_department'])->map(fn ($row) => [
            $row['department'], $row['count'], $row['avg_days'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'], $row['title'], $row['department'], $row['type'],
            $row['total_days'], $row['created_at'], $row['archived_at'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_department'), [
                __('common.department'),
                __('reports.count'),
                __('reports.avg_days'),
            ], $deptRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.reference_number'),
                __('common.title'),
                __('common.department'),
                __('common.transaction_type'),
                __('reports.export.col.total_days'),
                __('reports.created_at'),
                __('reports.archived_at'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function userActivity(array $data): self
    {
        $rows = collect($data['details'])->map(fn ($row) => [
            $row['user'], $row['department'],
            $row['created'], $row['transitions'], $row['uploads'], $row['total'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.user_activity'), [
                __('reports.user'),
                __('common.department'),
                __('reports.created_transactions'),
                __('reports.transitions'),
                __('reports.uploads'),
                __('reports.sum'),
            ], $rows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function referenceNumbers(array $data): self
    {
        $dupRows = collect($data['duplicates'])->map(fn ($row) => [
            $row['reference_number'], $row['count'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'], $row['year'], $row['month'], $row['title'],
            $row['transaction'], $row['department'], $row['uploader'], $row['date'],
            $row['is_duplicate'] ? __('common.yes') : __('common.no'),
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.duplicates'), [
                __('reports.reference_number'),
                __('reports.export.col.repetition'),
            ], $dupRows),
            new ReportSheetExport(__('reports.details'), [
                __('reports.reference_number'),
                __('reports.year'),
                __('reports.month'),
                __('common.title'),
                __('dashboard.transaction'),
                __('common.department'),
                __('reports.uploader'),
                __('common.date'),
                __('reports.export.col.duplicate'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function folderDistribution(array $data): self
    {
        $folderRows = collect($data['by_folder'])->map(fn ($row) => [
            $row['folder'], $row['department'], $row['transactions'], $row['attachments'],
        ])->all();

        $detailRows = collect($data['details'])->map(fn ($row) => [
            $row['reference_number'], $row['title'], $row['folder'], $row['department'],
            $row['type'], $row['status'], $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_folder'), [
                __('reports.export.col.folder'),
                __('common.department'),
                __('reports.transactions'),
                __('reports.attachments'),
            ], $folderRows),
            new ReportSheetExport(__('reports.details'), [
                __('common.reference_number'),
                __('common.title'),
                __('reports.export.col.folder'),
                __('common.department'),
                __('common.transaction_type'),
                __('common.status'),
                __('common.date'),
            ], $detailRows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function confidentialDocuments(array $data): self
    {
        $rows = collect($data['details'])->map(fn ($row) => [
            $row['source'], $row['title'], $row['reference_number'], $row['department'],
            $row['uploader'], $row['date'],
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.confidential'), [
                __('reports.source'),
                __('common.title'),
                __('reports.reference_number'),
                __('common.department'),
                __('reports.uploader'),
                __('common.date'),
            ], $rows),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function systemOperations(array $data): self
    {
        $summaryRows = collect($data['by_type'] ?? [])->map(fn ($row) => [
            $row['label'], $row['count'],
        ])->all();

        $detailRows = collect($data['events'] ?? [])->map(fn ($row) => [
            $row['occurred_at_display'],
            $row['event_type'],
            $row['label'],
            $row['actor'],
            $row['department'],
            $row['folder'],
            $row['transaction_ref'],
            $row['transaction_title'],
            $row['details'],
            $row['notes'] ?? '',
        ])->all();

        return new self([
            new ReportSheetExport(__('reports.export.sheet.by_event_type'), [
                __('reports.event_type_label'),
                __('reports.count'),
            ], $summaryRows),
            new ReportSheetExport(__('reports.export.sheet.operations_log'), [
                __('common.date'),
                __('reports.event_type_label'),
                __('reports.export.col.action'),
                __('reports.user'),
                __('common.department'),
                __('reports.export.col.folder'),
                __('common.reference_number'),
                __('common.title'),
                __('reports.export.col.details'),
                __('reports.export.col.notes'),
            ], $detailRows),
        ]);
    }
}
