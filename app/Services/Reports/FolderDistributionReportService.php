<?php

namespace App\Services\Reports;

use App\Models\Folder;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Facades\DB;

class FolderDistributionReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $baseQuery = $this->scope->applyTransactionFilters($this->scope->transactionsQuery(), $filter);

        $withoutFolder = (clone $baseQuery)->whereNull('folder_id')->count();

        $rows = (clone $baseQuery)
            ->whereNotNull('folder_id')
            ->join('folders', 'folders.id', '=', 'transactions.folder_id')
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->select(
                'folders.id as folder_id',
                'folders.name as folder_name',
                'departments.name as department_name',
                DB::raw('COUNT(transactions.id) as transaction_count')
            )
            ->groupBy('folders.id', 'folders.name', 'departments.name')
            ->orderByDesc('transaction_count')
            ->get();

        $folderIds = $rows->pluck('folder_id')->all();
        $attachmentCounts = [];

        if ($folderIds !== []) {
            $attachmentCounts = TransactionAttachment::query()
                ->join('transactions', 'transactions.id', '=', 'transaction_attachments.transaction_id')
                ->whereIn('transactions.folder_id', $folderIds)
                ->select('transactions.folder_id', DB::raw('COUNT(*) as total'))
                ->groupBy('transactions.folder_id')
                ->pluck('total', 'transactions.folder_id')
                ->all();
        }

        $folderLabels = $this->folderPathMap($this->scope->scopedDepartmentIds());

        $byFolder = $rows->map(fn ($row) => [
            'folder' => $folderLabels[$row->folder_id] ?? $row->folder_name,
            'department' => $row->department_name,
            'transactions' => (int) $row->transaction_count,
            'attachments' => (int) ($attachmentCounts[$row->folder_id] ?? 0),
        ]);

        $byDepartment = $byFolder->groupBy('department')
            ->map(fn ($group, $name) => [
                'department' => $name,
                'transactions' => (int) $group->sum('transactions'),
                'attachments' => (int) $group->sum('attachments'),
                'folders' => $group->count(),
            ])
            ->sortByDesc('transactions')
            ->values();

        $details = (clone $baseQuery)
            ->with(['folder', 'department', 'transactionType', 'status'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($transaction) => [
                'reference_number' => $transaction->reference_number,
                'title' => $transaction->title,
                'folder' => $transaction->folder_id
                    ? ($folderLabels[$transaction->folder_id] ?? $transaction->folder?->name ?? '—')
                    : __('reports.no_folder'),
                'department' => $transaction->department?->name ?? '—',
                'type' => $transaction->transactionType?->name ?? '—',
                'status' => $transaction->status?->name ?? '—',
                'status_color' => $transaction->status?->color,
                'date' => $transaction->transaction_date?->format('Y-m-d') ?? $transaction->created_at->format('Y-m-d'),
            ]);

        return [
            'total_transactions' => (int) $byFolder->sum('transactions') + $withoutFolder,
            'total_folders' => $byFolder->count(),
            'without_folder' => $withoutFolder,
            'by_folder' => $byFolder,
            'by_department' => $byDepartment,
            'details' => $details,
        ];
    }

    /** @return array<int, string> */
    private function folderPathMap(?array $departmentIds): array
    {
        $folders = Folder::scopedQuery($departmentIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $byId = $folders->keyBy('id');
        $map = [];

        foreach ($folders as $folder) {
            $parts = [];
            $current = $folder;

            while ($current) {
                array_unshift($parts, $current->name);
                $current = $current->parent_id ? $byId->get($current->parent_id) : null;
            }

            $map[$folder->id] = implode(' / ', $parts);
        }

        return $map;
    }
}
