<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Facades\DB;

class ReferenceNumbersReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $attachmentQuery = $this->scope->applyAttachmentFilters(
            TransactionAttachment::query()->whereNotNull('reference_number')->where('reference_number', '!=', ''),
            $filter
        );

        $totalWithRef = (clone $attachmentQuery)->count();

        $duplicateNumbers = (clone $attachmentQuery)
            ->select('reference_number', DB::raw('COUNT(*) as total'))
            ->groupBy('reference_number')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'reference_number' => $row->reference_number,
                'count' => (int) $row->total,
            ]);

        $duplicateRefNumbers = $duplicateNumbers->pluck('reference_number')->all();

        $byYear = (clone $attachmentQuery)
            ->select('reference_year', DB::raw('COUNT(*) as total'))
            ->whereNotNull('reference_year')
            ->groupBy('reference_year')
            ->orderByDesc('reference_year')
            ->get()
            ->map(fn ($row) => [
                'year' => (string) $row->reference_year,
                'count' => (int) $row->total,
            ]);

        $byDepartment = (clone $attachmentQuery)
            ->join('transactions', 'transactions.id', '=', 'transaction_attachments.transaction_id')
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->select('departments.name as department_name', DB::raw('COUNT(*) as total'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department_name,
                'count' => (int) $row->total,
            ]);

        $transactionRefs = $this->scope
            ->applyTransactionFilters(Transaction::query(), $filter)
            ->whereNotNull('reference_number')
            ->count();

        $details = (clone $attachmentQuery)
            ->with(['transaction.department', 'uploader'])
            ->orderByDesc('transaction_attachments.created_at')
            ->get()
            ->map(fn ($attachment) => [
                'reference_number' => $attachment->reference_number,
                'year' => $attachment->reference_year ?? '—',
                'month' => $attachment->reference_month ?? '—',
                'title' => $attachment->displayName(),
                'transaction' => $attachment->transaction?->reference_number ?? '—',
                'department' => $attachment->transaction?->department?->name ?? '—',
                'uploader' => $attachment->uploader?->name ?? '—',
                'date' => $attachment->created_at->format('Y-m-d'),
                'is_duplicate' => in_array($attachment->reference_number, $duplicateRefNumbers, true),
            ]);

        return [
            'total_attachment_refs' => $totalWithRef,
            'total_transaction_refs' => $transactionRefs,
            'duplicate_count' => $duplicateNumbers->count(),
            'by_year' => $byYear,
            'by_department' => $byDepartment,
            'duplicates' => $duplicateNumbers,
            'details' => $details,
        ];
    }
}
