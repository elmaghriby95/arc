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
        $status = $transaction->status;

        if (! $status) {
            return false;
        }

        $eligible = $this->eligibleStatuses();

        if ($eligible->isEmpty()) {
            return false;
        }

        $statusId = (int) $status->id;

        if ($eligible->contains(fn (TransactionStatus $candidate) => (int) $candidate->id === $statusId)) {
            return true;
        }

        // Handle replaced/inactive status rows that keep the same workflow code.
        if ($status->code !== null && $status->code !== '') {
            return $eligible->contains(fn (TransactionStatus $candidate) => $candidate->code === $status->code);
        }

        return false;
    }

    public function isOnLoan(Transaction $transaction): bool
    {
        $status = $transaction->lending_status;

        if ($status === null) {
            return false;
        }

        if ($status instanceof TransactionLendingStatus) {
            return $status === TransactionLendingStatus::OnLoan;
        }

        return TransactionLendingStatus::tryFrom((string) $status) === TransactionLendingStatus::OnLoan;
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
        return $this->blockingReason($user, $transaction) === null;
    }

    public function canShowRequestButton(User $user, Transaction $transaction): bool
    {
        if (! $user->hasPermission('lending-requests.request')) {
            return false;
        }

        return $user->canAccessTransaction($transaction);
    }

    public function blockingReason(User $user, Transaction $transaction): ?string
    {
        if (! $user->hasPermission('lending-requests.request')) {
            return __('lending_requests.block.no_permission');
        }

        if (! $user->canAccessTransaction($transaction)) {
            return __('lending_requests.block.no_access');
        }

        if (! $this->isTransactionEligible($transaction)) {
            $statusNames = $this->eligibleStatuses()->pluck('name')->implode('، ');
            $currentStatus = $transaction->status?->name ?? '—';

            return __('lending_requests.block.status_not_eligible', [
                'statuses' => $statusNames !== '' ? $statusNames : '—',
                'current' => $currentStatus,
            ]);
        }

        if ($this->isOnLoan($transaction)) {
            return __('lending_requests.block.on_loan');
        }

        if ($this->hasActiveRequest($transaction)) {
            return __('lending_requests.block.active_request');
        }

        return null;
    }
}
