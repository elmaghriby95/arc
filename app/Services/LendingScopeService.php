<?php

namespace App\Services;

use App\Models\LendingRequest;
use App\Models\Transaction;
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
        // Employee/requester without queue permissions only sees their own requests.
        if (! $this->isLendingQueueActor($user)) {
            $query->where('requested_by', $user->id);

            return;
        }

        if ($this->hasUnrestrictedAccess($user)) {
            return;
        }

        // Review/archive (workflow-only) with lending queue access see all units' requests.
        if ($this->isWorkflowOnlyActor($user)) {
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

        if (! $this->isLendingQueueActor($user)) {
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
     * and only add a path for workflow-stage roles (review/archive) that hold lending permissions —
     * they manage the lending queue across all units.
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

        return $this->isLendingQueueActor($user);
    }

    private function isWorkflowOnlyActor(User $user): bool
    {
        return ! $user->hasPermission('transactions.create')
            && ! $this->hasUnrestrictedAccess($user);
    }

    public function isLendingQueueActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.view')
            || $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }
}
