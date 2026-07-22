<?php

namespace App\Services\Reports;

use App\Enums\WorkflowAction;
use App\Models\TransactionStatusHistory;
use App\Support\Reports\ReportFilter;

class StatusHistoryReportService
{
    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter, ReportScopeService $scope): array
    {
        $baseQuery = $scope->applyHistoryFilters(
            TransactionStatusHistory::query(),
            $filter
        );

        $items = (clone $baseQuery)
            ->with([
                'transaction.department',
                'transaction.transactionType',
                'fromStatus',
                'toStatus',
                'changedBy',
            ])
            ->orderByDesc('created_at')
            ->get();

        $byAction = $items->groupBy(fn ($item) => $item->action ?? 'unknown')
            ->map(fn ($group, $action) => [
                'action' => $action,
                'label' => $this->actionLabel($action),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $byUser = $items->groupBy(fn ($item) => $item->changedBy?->name ?? '—')
            ->map(fn ($group, $name) => [
                'user' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(15)
            ->values();

        $details = $items->map(fn ($history) => [
            'date' => $history->created_at?->format('Y-m-d H:i') ?? '—',
            'reference_number' => $history->transaction?->reference_number ?? '—',
            'title' => $history->transaction?->title ?? '—',
            'department' => $history->transaction?->department?->name ?? '—',
            'from_status' => $history->fromStatus?->name ?? '—',
            'to_status' => $history->toStatus?->name ?? '—',
            'to_status_color' => $history->toStatus?->color,
            'action' => $this->actionLabel($history->action),
            'changed_by' => $history->changedBy?->name ?? '—',
            'notes' => $history->notes ?? '—',
        ]);

        return [
            'total' => $items->count(),
            'by_action' => $byAction,
            'by_user' => $byUser,
            'details' => $details,
        ];
    }

    private function actionLabel(?string $action): string
    {
        if ($action === null) {
            return '—';
        }

        return WorkflowAction::tryFrom($action)?->label() ?? $action;
    }
}
