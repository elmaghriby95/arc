<?php

namespace App\Http\Controllers;

use App\Enums\LendingRequestStatus;
use App\Models\LendingRequest;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $transactionsQuery = $this->dashboardTransactionsQuery($user);
        $attachmentsQuery = $this->dashboardAttachmentsQuery($user);
        $statCards = $this->statCards($user, $transactionsQuery, $attachmentsQuery);

        return view('dashboard', [
            'heroMetric' => $statCards[0] ?? [
                'label' => __('dashboard.available_widgets'),
                'value' => 0,
            ],
            'statCards' => $statCards,
            'orgBreadcrumb' => $user->orgBreadcrumb(),
        ]);
    }

    /** @return Builder<Transaction> */
    private function dashboardTransactionsQuery(User $user): Builder
    {
        $query = Transaction::query();
        $this->applyDashboardTransactionScope($query, $user);

        return $query;
    }

    /** @return Builder<TransactionAttachment> */
    private function dashboardAttachmentsQuery(User $user): Builder
    {
        return TransactionAttachment::query()
            ->whereHas('transaction', fn (Builder $query) => $this->applyDashboardTransactionScope($query, $user));
    }

    /** @param Builder<Transaction> $query */
    private function applyDashboardTransactionScope(Builder $query, User $user): void
    {
        if ($this->hasGlobalDashboardScope($user)) {
            return;
        }

        if (! $user->department_id) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where('department_id', $user->department_id);
    }

    private function hasGlobalDashboardScope(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('transactions.view-all');
    }

    /**
     * @param Builder<Transaction> $transactionsQuery
     * @param Builder<TransactionAttachment> $attachmentsQuery
     * @return list<array{key: string, label: string, value: int, color: string, icon: string, url: string|null}>
     */
    private function statCards(User $user, Builder $transactionsQuery, Builder $attachmentsQuery): array
    {
        $reviewStatusIds = $this->reviewStatusIds($user);

        return [
            $this->statCard(
                'transactions',
                __('dashboard.total_transactions'),
                (clone $transactionsQuery)->count(),
                'indigo',
                'transactions',
                $user->hasPermission('transactions.view') ? route('transactions.index') : null,
            ),
            $this->statCard(
                'documents',
                __('dashboard.total_documents'),
                (clone $attachmentsQuery)->count(),
                'cyan',
                'documents',
                $user->hasPermission('documents.view') ? route('documents.index') : null,
            ),
            $this->statCard(
                'review',
                __('dashboard.pending_review'),
                $reviewStatusIds === []
                    ? 0
                    : (clone $transactionsQuery)->whereIn('transaction_status_id', $reviewStatusIds)->count(),
                'amber',
                'review',
                $user->hasPermission('transactions.view') && $reviewStatusIds !== []
                    ? route('transactions.index', ['transaction_status_id' => $reviewStatusIds[0]])
                    : null,
            ),
            $this->statCard(
                'archived',
                __('dashboard.archived_transactions'),
                (clone $transactionsQuery)->whereHas('status', fn (Builder $query) => $query->where('is_final', true))->count(),
                'emerald',
                'archive',
                $user->hasPermission('transactions.view') ? route('transactions.index') : null,
            ),
            $this->statCard(
                'lending',
                __('dashboard.active_lending_requests'),
                $this->activeLendingRequestsCount($user),
                'violet',
                'lending',
                $this->canViewLendingSummary($user) ? route('lending-requests.index') : null,
            ),
        ];
    }

    /** @return array{key: string, label: string, value: int, color: string, icon: string, url: string|null} */
    private function statCard(string $key, string $label, int $value, string $color, string $icon, ?string $url = null): array
    {
        return compact('key', 'label', 'value', 'color', 'icon', 'url');
    }

    /** @return list<int> */
    private function reviewStatusIds(User $user): array
    {
        return TransactionStatus::query()
            ->where('is_active', true)
            ->where('is_initial', false)
            ->where('is_final', false)
            ->whereNotNull('required_permission')
            ->get(['id', 'required_permission'])
            ->filter(fn (TransactionStatus $status) => $user->hasPermission($status->required_permission))
            ->pluck('id')
            ->all();
    }

    private function canViewLendingSummary(User $user): bool
    {
        return $this->hasAnyPermission($user, [
            'lending-requests.view',
            'lending-requests.request',
            'lending-requests.review',
            'lending-requests.handover',
        ]);
    }

    private function activeLendingRequestsCount(User $user): int
    {
        return LendingRequest::query()
            ->whereIn('status', array_map(
                fn (LendingRequestStatus $status) => $status->value,
                LendingRequestStatus::activeCases(),
            ))
            ->whereHas('transaction', fn (Builder $query) => $this->applyDashboardTransactionScope($query, $user))
            ->count();
    }

    /** @param list<string> $permissions */
    private function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
