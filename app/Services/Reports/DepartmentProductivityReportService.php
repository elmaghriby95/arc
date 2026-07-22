<?php

namespace App\Services\Reports;

use App\Models\TransactionStatus;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Facades\DB;

class DepartmentProductivityReportService
{
    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter, ReportScopeService $scope): array
    {
        $statuses = TransactionStatus::where('is_active', true)->orderBy('sort_order')->get();
        $finalStatusIds = $statuses->where('is_final', true)->pluck('id')->all();

        $baseQuery = $scope->applyTransactionFilters($scope->transactionsQuery(), $filter);

        $rows = (clone $baseQuery)
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(*) as created_total'),
                DB::raw('SUM(CASE WHEN transaction_statuses.is_final = 1 THEN 1 ELSE 0 END) as archived_total'),
                DB::raw('SUM(CASE WHEN transaction_statuses.is_initial = 1 THEN 1 ELSE 0 END) as draft_total'),
                DB::raw('SUM(CASE WHEN transaction_statuses.is_final = 0 AND transaction_statuses.is_initial = 0 THEN 1 ELSE 0 END) as in_progress_total')
            )
            ->join('transaction_statuses', 'transaction_statuses.id', '=', 'transactions.transaction_status_id')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('created_total')
            ->get();

        $statusBreakdown = (clone $baseQuery)
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
            ->get()
            ->groupBy('department_id');

        $completedInPeriod = [];
        if ($finalStatusIds !== []) {
            $completedQuery = DB::table('transaction_status_histories as h')
                ->join('transactions as t', 't.id', '=', 'h.transaction_id')
                ->whereIn('h.to_status_id', $finalStatusIds)
                ->select('t.department_id', DB::raw('COUNT(DISTINCT t.id) as total'))
                ->groupBy('t.department_id');

            if ($filter->dateFrom) {
                $completedQuery->where('h.created_at', '>=', $filter->dateFrom);
            }

            if ($filter->dateTo) {
                $completedQuery->where('h.created_at', '<=', $filter->dateTo);
            }

            if (($scopedIds = $scope->scopedDepartmentIds()) !== null) {
                $completedQuery->whereIn('t.department_id', $scopedIds);
            }

            if ($filter->departmentId) {
                $completedQuery->where('t.department_id', $filter->departmentId);
            }

            $completedInPeriod = $completedQuery->pluck('total', 'department_id')->all();
        }

        $details = $rows->map(function ($row) use ($statusBreakdown, $completedInPeriod) {
            $statusCells = [];
            foreach ($statusBreakdown->get($row->department_id, collect()) as $cell) {
                $statusCells[$cell->status_name] = [
                    'count' => (int) $cell->total,
                    'color' => $cell->status_color,
                ];
            }

            $archived = (int) ($row->archived_total ?? 0);
            $created = (int) $row->created_total;
            $completionRate = $created > 0 ? round(($archived / $created) * 100, 1) : 0;

            return [
                'department' => $row->department_name,
                'created' => $created,
                'draft' => (int) $row->draft_total,
                'in_progress' => (int) $row->in_progress_total,
                'archived' => $archived,
                'completed_in_period' => (int) ($completedInPeriod[$row->department_id] ?? 0),
                'completion_rate' => $completionRate,
                'status_cells' => $statusCells,
            ];
        });

        $totals = [
            'departments' => $details->count(),
            'created' => (int) $details->sum('created'),
            'archived' => (int) $details->sum('archived'),
            'in_progress' => (int) $details->sum('in_progress'),
            'draft' => (int) $details->sum('draft'),
        ];

        return [
            'details' => $details,
            'totals' => $totals,
            'statuses' => $statuses,
        ];
    }
}
