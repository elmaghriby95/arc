<?php

namespace App\Services\Reports;

use App\Models\TransactionStatus;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionPipelineReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $statuses = TransactionStatus::where('is_active', true)->orderBy('sort_order')->get();
        $baseQuery = $this->scope->applyTransactionFilters($this->scope->transactionsQuery(), $filter);

        $statusCounts = (clone $baseQuery)
            ->select('transaction_status_id', DB::raw('COUNT(*) as total'))
            ->groupBy('transaction_status_id')
            ->pluck('total', 'transaction_status_id');

        $summary = $statuses->map(fn (TransactionStatus $status) => [
            'id' => $status->id,
            'name' => $status->name,
            'code' => $status->code,
            'color' => $status->color,
            'count' => (int) ($statusCounts[$status->id] ?? 0),
        ]);

        $byDepartment = (clone $baseQuery)
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->join('transaction_statuses', 'transaction_statuses.id', '=', 'transactions.transaction_status_id')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                'transaction_statuses.id as status_id',
                'transaction_statuses.name as status_name',
                'transaction_statuses.color as status_color',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('departments.id', 'departments.name', 'transaction_statuses.id', 'transaction_statuses.name', 'transaction_statuses.color')
            ->orderBy('departments.name')
            ->get();

        $byType = (clone $baseQuery)
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transactions.transaction_type_id')
            ->join('transaction_statuses', 'transaction_statuses.id', '=', 'transactions.transaction_status_id')
            ->select(
                'transaction_types.id as type_id',
                DB::raw("COALESCE(transaction_types.name, '—') as type_name"),
                'transaction_statuses.id as status_id',
                'transaction_statuses.name as status_name',
                'transaction_statuses.color as status_color',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('transaction_types.id', 'transaction_types.name', 'transaction_statuses.id', 'transaction_statuses.name', 'transaction_statuses.color')
            ->orderBy('type_name')
            ->get();

        $details = (clone $baseQuery)
            ->with(['department', 'transactionType', 'status', 'creator'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($transaction) => [
                'reference_number' => $transaction->reference_number,
                'title' => $transaction->title,
                'department' => $transaction->department?->name ?? '—',
                'type' => $transaction->transactionType?->name ?? '—',
                'status' => $transaction->status?->name ?? '—',
                'status_color' => $transaction->status?->color,
                'creator' => $transaction->creator?->name ?? '—',
                'date' => $transaction->transaction_date?->format('Y-m-d') ?? $transaction->created_at->format('Y-m-d'),
            ]);

        return [
            'summary' => $summary,
            'total' => (int) $summary->sum('count'),
            'by_department' => $this->pivotMatrix($byDepartment, 'department_name', 'status_name', 'status_color'),
            'by_type' => $this->pivotMatrix($byType, 'type_name', 'status_name', 'status_color'),
            'details' => $details,
            'statuses' => $statuses,
        ];
    }

    /** @param Collection<int, object> $rows */
    private function pivotMatrix(Collection $rows, string $rowKey, string $colKey, string $colorKey): array
    {
        $matrix = [];

        foreach ($rows as $row) {
            $rowLabel = $row->{$rowKey};
            $colLabel = $row->{$colKey};

            if (! isset($matrix[$rowLabel])) {
                $matrix[$rowLabel] = [
                    'label' => $rowLabel,
                    'cells' => [],
                    'total' => 0,
                ];
            }

            $matrix[$rowLabel]['cells'][$colLabel] = [
                'count' => (int) $row->total,
                'color' => $row->{$colorKey},
            ];
            $matrix[$rowLabel]['total'] += (int) $row->total;
        }

        return array_values($matrix);
    }
}
