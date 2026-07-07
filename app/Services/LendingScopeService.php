<?php

namespace App\Services;

use App\Models\LendingRequest;
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
        if ($this->hasUnrestrictedAccess($user)) {
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
        if ($request->requested_by === $user->id) {
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

        return $user->canAccessTransaction($request->transaction);
    }
}
