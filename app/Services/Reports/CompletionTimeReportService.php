<?php

namespace App\Services\Reports;

use App\Models\TransactionStatus;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Collection;

class CompletionTimeReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $finalStatusIds = TransactionStatus::query()
            ->where('is_active', true)
            ->where('is_final', true)
            ->pluck('id')
            ->all();

        $transactions = $this->scope
            ->applyTransactionFilters($this->scope->transactionsQuery(), $filter)
            ->whereHas('status', fn ($query) => $query->where('is_final', true))
            ->with([
                'department',
                'transactionType',
                'status',
                'statusHistories.fromStatus',
                'statusHistories.toStatus',
            ])
            ->orderByDesc('created_at')
            ->get();

        $items = $transactions->map(function ($transaction) use ($finalStatusIds) {
            $archivedAt = $transaction->statusHistories
                ->filter(fn ($history) => in_array($history->to_status_id, $finalStatusIds, true))
                ->sortByDesc('created_at')
                ->first()?->created_at ?? $transaction->updated_at;

            $totalDays = max(0, (int) $transaction->created_at->diffInDays($archivedAt));
            $stageDays = $this->stageDurations($transaction->statusHistories->sortBy('created_at'));

            return [
                'reference_number' => $transaction->reference_number,
                'title' => $transaction->title,
                'department' => $transaction->department?->name ?? '—',
                'type' => $transaction->transactionType?->name ?? '—',
                'total_days' => $totalDays,
                'created_at' => $transaction->created_at->format('Y-m-d'),
                'archived_at' => $archivedAt->format('Y-m-d'),
                'stages' => $stageDays,
            ];
        });

        $completed = $items->count();
        $avgTotal = $completed > 0 ? (int) round($items->avg('total_days')) : 0;
        $maxTotal = $completed > 0 ? (int) $items->max('total_days') : 0;

        $byDepartment = $items->groupBy('department')
            ->map(fn (Collection $group, $name) => [
                'department' => $name,
                'count' => $group->count(),
                'avg_days' => (int) round($group->avg('total_days')),
            ])
            ->sortByDesc('count')
            ->values();

        $byType = $items->groupBy('type')
            ->map(fn (Collection $group, $name) => [
                'type' => $name,
                'count' => $group->count(),
                'avg_days' => (int) round($group->avg('total_days')),
            ])
            ->sortByDesc('count')
            ->values();

        return [
            'total' => $completed,
            'avg_days' => $avgTotal,
            'max_days' => $maxTotal,
            'by_department' => $byDepartment,
            'by_type' => $byType,
            'details' => $items,
        ];
    }

    /** @param Collection<int, \App\Models\TransactionStatusHistory> $histories */
    private function stageDurations(Collection $histories): array
    {
        $stages = [];
        $previous = null;

        foreach ($histories as $history) {
            if ($previous !== null && $history->created_at) {
                $fromName = $previous->toStatus?->name ?? '—';
                $days = max(0, (int) $previous->created_at->diffInDays($history->created_at));
                $stages[] = [
                    'from' => $fromName,
                    'to' => $history->toStatus?->name ?? '—',
                    'days' => $days,
                ];
            }

            $previous = $history;
        }

        return $stages;
    }
}
