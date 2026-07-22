<?php

namespace App\Services\Reports;

use App\Models\TransactionAttachment;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Facades\DB;

class DocumentsAttachmentsReportService
{
    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter, ReportScopeService $scope): array
    {
        $baseQuery = $scope->applyAttachmentFilters(
            TransactionAttachment::query(),
            $filter
        );

        $totalCount = (clone $baseQuery)->count();
        $totalSize = (int) (clone $baseQuery)->sum('file_size');

        $byDepartment = (clone $baseQuery)
            ->join('transactions', 'transactions.id', '=', 'transaction_attachments.transaction_id')
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->select(
                'departments.name as department_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COALESCE(SUM(transaction_attachments.file_size), 0) as total_size')
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department_name,
                'count' => (int) $row->total,
                'size' => (int) $row->total_size,
                'size_formatted' => $this->formatBytes((int) $row->total_size),
            ]);

        $attachments = (clone $baseQuery)
            ->with(['transaction.department', 'transaction.transactionType', 'uploader'])
            ->orderByDesc('transaction_attachments.created_at')
            ->get();

        $byKind = [];
        foreach ($attachments as $attachment) {
            $kind = $attachment->fileKind();
            if (! isset($byKind[$kind])) {
                $byKind[$kind] = ['kind' => $kind, 'label' => $this->kindLabel($kind), 'count' => 0, 'size' => 0];
            }
            $byKind[$kind]['count']++;
            $byKind[$kind]['size'] += (int) ($attachment->effectiveFileSize() ?? 0);
        }

        $byKind = collect($byKind)
            ->map(fn ($row) => [...$row, 'size_formatted' => $this->formatBytes($row['size'])])
            ->sortByDesc('count')
            ->values();

        $byMime = $attachments
            ->groupBy(fn ($a) => $a->effectiveMimeType() ?? __('reports.file_kind.unknown'))
            ->map(fn ($group, $mime) => [
                'mime' => $mime,
                'count' => $group->count(),
                'size' => $group->sum(fn ($a) => (int) ($a->effectiveFileSize() ?? 0)),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->map(fn ($row) => [...$row, 'size_formatted' => $this->formatBytes($row['size'])])
            ->values();

        $details = $attachments->map(fn ($attachment) => [
            'title' => $attachment->displayName(),
            'reference_number' => $attachment->reference_number ?? $attachment->transaction?->reference_number ?? '—',
            'transaction' => $attachment->transaction?->reference_number ?? '—',
            'department' => $attachment->transaction?->department?->name ?? '—',
            'type' => $attachment->transaction?->transactionType?->name ?? '—',
            'kind' => $this->kindLabel($attachment->fileKind()),
            'mime' => $attachment->effectiveMimeType() ?? '—',
            'size' => $this->formatBytes((int) ($attachment->effectiveFileSize() ?? 0)),
            'uploader' => $attachment->uploader?->name ?? '—',
            'date' => $attachment->created_at->format('Y-m-d'),
        ]);

        return [
            'total_count' => $totalCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'by_department' => $byDepartment,
            'by_kind' => $byKind,
            'by_mime' => $byMime,
            'details' => $details,
        ];
    }

    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'pdf' => 'PDF',
            'word' => 'Word',
            'excel' => 'Excel',
            'image' => __('reports.file_kind.image'),
            default => __('reports.file_kind.other'),
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
