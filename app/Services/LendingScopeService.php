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

        // Stage actors (review / handover) without full queue view: only their workflow statuses, all units.
        if ($this->isLendingDecisionActor($user) && ! $this->hasFullLendingQueueView($user)) {
            $statuses = $this->visibleStatusesForDecisionActor($user);

            if ($statuses !== []) {
                $query->whereIn('status', $statuses);
            }

            return;
        }

        if ($this->hasUnrestrictedAccess($user)) {
            return;
        }

        // lending-requests.view (e.g. unit managers): department scope, all statuses.
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

        if ($this->isLendingDecisionActor($user) && ! $this->hasFullLendingQueueView($user)) {
            $status = $request->status instanceof LendingRequestStatus
                ? $request->status->value
                : (string) $request->status;

            return in_array($status, $this->visibleStatusesForDecisionActor($user), true);
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
     * Lending queue access for approve / handover / return actions.
     */
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

    private function hasFullLendingQueueView(User $user): bool
    {
        if ($this->hasUnrestrictedAccess($user)) {
            return true;
        }

        // Unit managers (create + view) keep the full status history in their unit.
        // Pure review/archive stage actors stay limited to their workflow statuses.
        return $user->hasPermission('lending-requests.view')
            && $user->hasPermission('transactions.create');
    }
}
