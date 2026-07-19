<?php

namespace App\Services\Reports;

use App\Support\Reports\ReportFilter;
use Illuminate\Support\Facades\DB;

class StaleTransactionsReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $staleDays = $filter->staleDays;
        $today = today()->toDateString();
        $baseQuery = $this->scope->applyTransactionFilters($this->scope->transactionsQuery(), $filter);

        $items = (clone $baseQuery)
            ->join('transaction_statuses', 'transaction_statuses.id', '=', 'transactions.transaction_status_id')
            ->where('transaction_statuses.is_final', false)
            ->whereRaw(
                'DATEDIFF(?, COALESCE((SELECT MAX(h.created_at) FROM transaction_status_histories h WHERE h.transaction_id = transactions.id), transactions.created_at)) >= ?',
                [$today, $staleDays]
            )
            ->select('transactions.*')
            ->selectRaw(
                'DATEDIFF(?, COALESCE((SELECT MAX(h.created_at) FROM transaction_status_histories h WHERE h.transaction_id = transactions.id), transactions.created_at)) as days_stale',
                [$today]
            )
            ->orderByDesc('days_stale')
            ->with(['department', 'transactionType', 'status', 'creator'])
            ->get();

        $byDepartment = $items->groupBy(fn ($item) => $item->department?->name ?? '—')
            ->map(fn ($group, $name) => [
                'department' => $name,
                'count' => $group->count(),
                'avg_days' => (int) round($group->avg('days_stale')),
            ])
            ->sortByDesc('count')
            ->values();

        $byStatus = $items->groupBy(fn ($item) => $item->status?->name ?? '—')
            ->map(fn ($group, $name) => [
                'status' => $name,
                'color' => $group->first()->status?->color,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $details = $items->map(fn ($transaction) => [
            'reference_number' => $transaction->reference_number,
            'title' => $transaction->title,
            'department' => $transaction->department?->name ?? '—',
            'type' => $transaction->transactionType?->name ?? '—',
            'status' => $transaction->status?->name ?? '—',
            'status_color' => $transaction->status?->color,
            'creator' => $transaction->creator?->name ?? '—',
            'days_stale' => (int) $transaction->days_stale,
            'last_activity' => $transaction->updated_at->format('Y-m-d'),
        ]);

        return [
            'stale_days' => $staleDays,
            'total' => $items->count(),
            'avg_days' => $items->isEmpty() ? 0 : (int) round($items->avg('days_stale')),
            'max_days' => $items->isEmpty() ? 0 : (int) $items->max('days_stale'),
            'by_department' => $byDepartment,
            'by_status' => $byStatus,
            'details' => $details,
        ];
    }
}
