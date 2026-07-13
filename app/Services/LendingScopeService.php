<?php

namespace App\Services;

use App\Models\LendingRequest;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LendingScopeService
{
    public function hasUnrestrictedAccess(User $user): bool
    {
        return $user->hasPermission('transactions.view-all');
    }

    /** @param Builder<LendingRequest> $query */
    public function applyScopeToQuery(Builder $query, User $user): void
    {
        // Requesters without the queue-view permission only see their own requests.
        if (! $user->hasPermission('lending-requests.view')) {
            $query->where('requested_by', $user->id);

            return;
        }

        if ($this->hasUnrestrictedAccess($user)) {
            return;
        }

        // Workflow-stage actors (review/archive) with global visibility manage the full lending queue.
        if ($this->isWorkflowOnlyActor($user) && $this->hasGlobalLendingQueueScope($user)) {
            return;
        }

        $departmentIds = $user->orgScopeDepartmentIds();

        $query->where(function (Builder $outer) use ($user, $departmentIds) {
            $outer->where('requested_by', $user->id);

            if ($departmentIds === null) {
                $outer->orWhereHas('transaction');
            } elseif ($departmentIds !== []) {
                $outer->orWhereHas(
                    'transaction',
                    fn (Builder $transactionQuery) => $transactionQuery->whereIn('department_id', $departmentIds),
                );
            }
        });
    }

    public function canViewRequest(User $user, LendingRequest $request): bool
    {
        if ((int) $request->requested_by === (int) $user->id) {
            return true;
        }

        if (! $user->hasPermission('lending-requests.view')) {
            return false;
        }

        if ($this->hasUnrestrictedAccess($user)) {
            return true;
        }

        $request->loadMissing('transaction');

        if (! $request->transaction) {
            return false;
        }

        return $this->canAccessLendingTransaction($user, $request->transaction);
    }

    /**
     * Lending queue access: keep every existing canAccessTransaction path intact,
     * and only add a path for workflow-stage roles (review/archive) that hold lending permissions.
     */
    public function canAccessLendingTransaction(User $user, Transaction $transaction): bool
    {
        if ($user->canAccessTransaction($transaction)) {
            return true;
        }

        // Unit creators / admins already resolved above — do not broaden them.
        if (! $this->isWorkflowOnlyActor($user)) {
            return false;
        }

        if (! $this->isLendingQueueActor($user)) {
            return false;
        }

        return $this->transactionInLendingQueueScope($user, $transaction);
    }

    private function isWorkflowOnlyActor(User $user): bool
    {
        return ! $user->hasPermission('transactions.create')
            && ! $this->hasUnrestrictedAccess($user);
    }

    private function isLendingQueueActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.view')
            || $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }

    private function hasGlobalLendingQueueScope(User $user): bool
    {
        return TransactionStatus::query()
            ->where('is_active', true)
            ->where('is_initial', false)
            ->whereNotNull('required_permission')
            ->get()
            ->contains(
                fn (TransactionStatus $status) => $status->isGlobalScope()
                    && $user->hasPermission($status->required_permission),
            );
    }

    private function transactionInLendingQueueScope(User $user, Transaction $transaction): bool
    {
        if ($this->hasGlobalLendingQueueScope($user)) {
            return true;
        }

        $departmentIds = $user->orgScopeDepartmentIds();

        if ($departmentIds === null) {
            return true;
        }

        if ($departmentIds === []) {
            return false;
        }

        return in_array(
            (int) $transaction->department_id,
            array_map(intval(...), $departmentIds),
            true,
        );
    }
}
