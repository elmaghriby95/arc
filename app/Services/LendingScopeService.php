<?php

namespace App\Services;

use App\Enums\LendingRequestStatus;
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

        // Pure review/handover stage actors: all units, but only their workflow statuses.
        // Do NOT apply department scope here — that was wiping the queue for accounts without a unit.
        if ($this->isStageLimitedActor($user)) {
            $statuses = $this->visibleStatusesForDecisionActor($user);

            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }

            return;
        }

        // Managers / unit actors with lending-requests.view (+ usually transactions.create).
        $departmentIds = $user->orgScopeDepartmentIds();

        $query->where(function (Builder $outer) use ($user, $departmentIds) {
            $outer->where('requested_by', $user->id);

            // null = unrestricted; empty = no assigned unit — still show queue rather than wipe it.
            if ($departmentIds === null || $departmentIds === []) {
                $outer->orWhereHas('transaction');
            } else {
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

        if ($this->isStageLimitedActor($user)) {
            $status = $request->status instanceof LendingRequestStatus
                ? $request->status->value
                : (string) $request->status;

            return in_array($status, $this->visibleStatusesForDecisionActor($user), true);
        }

        $request->loadMissing('transaction');

        if (! $request->transaction) {
            return false;
        }

        return $this->canAccessLendingTransaction($user, $request->transaction);
    }

    public function canAccessLendingTransaction(User $user, Transaction $transaction): bool
    {
        if ($this->hasUnrestrictedAccess($user) || $this->isLendingDecisionActor($user)) {
            return true;
        }

        return $user->canAccessTransaction($transaction);
    }

    /** @return list<string> */
    public function visibleStatusesForDecisionActor(User $user): array
    {
        $statuses = [];

        if ($user->hasPermission('lending-requests.review')) {
            $statuses[] = LendingRequestStatus::PendingReview->value;
        }

        if ($user->hasPermission('lending-requests.handover')) {
            $statuses[] = LendingRequestStatus::PendingHandover->value;
            $statuses[] = LendingRequestStatus::OnLoan->value;
        }

        return array_values(array_unique($statuses));
    }

    public function isLendingQueueActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.view')
            || $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }

    public function isLendingDecisionActor(User $user): bool
    {
        return $user->hasPermission('lending-requests.review')
            || $user->hasPermission('lending-requests.handover');
    }

    /**
     * Review/archive actors who should only see their lending stage —
     * not unit managers who also happen to have review/handover.
     */
    public function isStageLimitedActor(User $user): bool
    {
        return $this->isLendingDecisionActor($user)
            && ! $user->hasPermission('transactions.create')
            && ! $this->hasUnrestrictedAccess($user);
    }
}
