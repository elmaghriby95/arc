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

        // Review / handover actors always see the full lending queue across all units.
        if ($this->hasUnrestrictedAccess($user) || $this->isLendingDecisionActor($user)) {
            return;
        }

        // lending-requests.view only (e.g. unit managers): department scope.
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

        if ($this->hasUnrestrictedAccess($user) || $this->isLendingDecisionActor($user)) {
            return true;
        }

        $request->loadMissing('transaction');

        if (! $request->transaction) {
            return false;
        }

        return $this->canAccessLendingTransaction($user, $request->transaction);
    }

    /**
     * Lending queue access for approve / handover / return actions.
     */
    public function canAccessLendingTransaction(User $user, Transaction $transaction): bool
    {
        if ($this->hasUnrestrictedAccess($user) || $this->isLendingDecisionActor($user)) {
            return true;
        }

        if ($user->canAccessTransaction($transaction)) {
            return true;
        }

        return false;
    }

    public function isLendingQueueActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.view')
            || $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }

    /** Actors who approve or hand over — they manage the queue across all units. */
    public function isLendingDecisionActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }
}
