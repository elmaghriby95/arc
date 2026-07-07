<?php

namespace App\Services;

use App\Enums\LendingRequestStatus;
use App\Enums\TransactionLendingStatus;
use App\Models\LendingRequest;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Support\Collection;

class LendingEligibilityService
{
    /** @return Collection<int, TransactionStatus> */
    public function eligibleStatuses(): Collection
    {
        $sequence = TransactionStatus::workflowSequence();

        if ($sequence->count() < 2) {
            return $sequence;
        }

        return $sequence->take(-2)->values();
    }

    /** @return list<int> */
    public function eligibleStatusIds(): array
    {
        return $this->eligibleStatuses()->pluck('id')->all();
    }

    public function isTransactionEligible(Transaction $transaction): bool
    {
        $transaction->loadMissing('status');

        if (! $transaction->status) {
            return false;
        }

        return in_array($transaction->transaction_status_id, $this->eligibleStatusIds(), true);
    }

    public function isOnLoan(Transaction $transaction): bool
    {
        return $transaction->lending_status === TransactionLendingStatus::OnLoan;
    }

    public function hasActiveRequest(Transaction $transaction): bool
    {
        return LendingRequest::query()
            ->where('transaction_id', $transaction->id)
            ->whereIn('status', array_map(
                fn (LendingRequestStatus $status) => $status->value,
                LendingRequestStatus::activeCases(),
            ))
            ->exists();
    }

    public function canUserRequest(User $user, Transaction $transaction): bool
    {
        if (! $user->hasPermission('lending-requests.request')) {
            return false;
        }

        if (! $user->canAccessTransaction($transaction)) {
            return false;
        }

        if (! $this->isTransactionEligible($transaction)) {
            return false;
        }

        if ($this->isOnLoan($transaction)) {
            return false;
        }

        if ($this->hasActiveRequest($transaction)) {
            return false;
        }

        return true;
    }
}
