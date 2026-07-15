<?php

namespace App\Services\Reports;

use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Support\Reports\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserActivityReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $scopedTransactionIds = $this->scope
            ->applyTransactionFilters($this->scope->transactionsQuery(), $filter)
            ->pluck('id');

        if ($scopedTransactionIds->isEmpty()) {
            return [
                'total_users' => 0,
                'total_actions' => 0,
                'details' => collect(),
            ];
        }

        $createdCounts = Transaction::query()
            ->whereIn('id', $scopedTransactionIds)
            ->select('created_by', DB::raw('COUNT(*) as total'))
            ->groupBy('created_by')
            ->pluck('total', 'created_by');

        $historyQuery = TransactionStatusHistory::query()->whereIn('transaction_id', $scopedTransactionIds);
        if ($filter->dateFrom) {
            $historyQuery->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $historyQuery->where('created_at', '<=', $filter->dateTo);
        }

        $transitionCounts = (clone $historyQuery)
            ->select('changed_by', DB::raw('COUNT(*) as total'))
            ->groupBy('changed_by')
            ->pluck('total', 'changed_by');

        $attachmentQuery = TransactionAttachment::query()->whereIn('transaction_id', $scopedTransactionIds);
        if ($filter->dateFrom) {
            $attachmentQuery->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $attachmentQuery->where('created_at', '<=', $filter->dateTo);
        }

        $uploadCounts = (clone $attachmentQuery)
            ->select('uploaded_by', DB::raw('COUNT(*) as total'))
            ->groupBy('uploaded_by')
            ->pluck('total', 'uploaded_by');

        $userIds = collect()
            ->merge($createdCounts->keys())
            ->merge($transitionCounts->keys())
            ->merge($uploadCounts->keys())
            ->unique()
            ->filter()
            ->values();

        $users = User::query()
            ->with(['department'])
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $details = $userIds->map(function ($userId) use ($users, $createdCounts, $transitionCounts, $uploadCounts) {
            $user = $users->get($userId);
            $created = (int) ($createdCounts[$userId] ?? 0);
            $transitions = (int) ($transitionCounts[$userId] ?? 0);
            $uploads = (int) ($uploadCounts[$userId] ?? 0);

            return [
                'user' => $user?->name ?? __('reports.unknown_user'),
                'department' => $user?->department?->name ?? '—',
                'created' => $created,
                'transitions' => $transitions,
                'uploads' => $uploads,
                'total' => $created + $transitions + $uploads,
            ];
        })
            ->sortByDesc('total')
            ->values();

        return [
            'total_users' => $details->count(),
            'total_actions' => (int) $details->sum('total'),
            'top_created' => $details->sortByDesc('created')->take(10)->values(),
            'top_transitions' => $details->sortByDesc('transitions')->take(10)->values(),
            'details' => $details,
        ];
    }
}
