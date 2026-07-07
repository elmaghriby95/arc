<?php

namespace App\Services;

use App\Enums\LendingRequestAction;
use App\Enums\LendingRequestStatus;
use App\Enums\TransactionLendingStatus;
use App\Models\LendingRequest;
use App\Models\LendingRequestHistory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LendingRequestService
{
    public function __construct(
        private LendingEligibilityService $eligibility,
    ) {}

    public function request(
        User $user,
        Transaction $transaction,
        ?string $purpose = null,
        ?string $dueDate = null,
    ): ?LendingRequest {
        if (! $this->eligibility->canUserRequest($user, $transaction)) {
            return null;
        }

        return DB::transaction(function () use ($user, $transaction, $purpose, $dueDate) {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedTransaction || ! $this->eligibility->canUserRequest($user, $lockedTransaction)) {
                return null;
            }

            $request = LendingRequest::create([
                'transaction_id' => $lockedTransaction->id,
                'requested_by' => $user->id,
                'status' => LendingRequestStatus::PendingReview,
                'purpose' => $purpose,
                'due_date' => $dueDate,
            ]);

            $this->recordHistory(
                $request,
                LendingRequestAction::Requested,
                null,
                LendingRequestStatus::PendingReview,
                $user,
            );

            return $request;
        });
    }

    public function review(
        User $user,
        LendingRequest $request,
        bool $approve,
        ?string $notes = null,
    ): bool {
        if (! $user->hasPermission('lending-requests.review')) {
            return false;
        }

        if ($request->status !== LendingRequestStatus::PendingReview) {
            return false;
        }

        if (! $approve && blank($notes)) {
            return false;
        }

        $request->loadMissing('transaction');

        if (! $request->transaction || ! $user->canAccessTransaction($request->transaction)) {
            return false;
        }

        return DB::transaction(function () use ($user, $request, $approve, $notes) {
            $locked = LendingRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== LendingRequestStatus::PendingReview) {
                return false;
            }

            if ($approve) {
                $locked->update([
                    'status' => LendingRequestStatus::PendingHandover,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);

                $this->recordHistory(
                    $locked,
                    LendingRequestAction::ReviewApproved,
                    LendingRequestStatus::PendingReview,
                    LendingRequestStatus::PendingHandover,
                    $user,
                    $notes,
                );
            } else {
                $locked->update([
                    'status' => LendingRequestStatus::Rejected,
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);

                $this->recordHistory(
                    $locked,
                    LendingRequestAction::ReviewRejected,
                    LendingRequestStatus::PendingReview,
                    LendingRequestStatus::Rejected,
                    $user,
                    $notes,
                );
            }

            return true;
        });
    }

    public function handover(
        User $user,
        LendingRequest $request,
        bool $confirm,
        ?string $notes = null,
    ): bool {
        if (! $user->hasPermission('lending-requests.handover')) {
            return false;
        }

        if ($request->status !== LendingRequestStatus::PendingHandover) {
            return false;
        }

        if (! $confirm && blank($notes)) {
            return false;
        }

        $request->loadMissing('transaction');

        if (! $request->transaction || ! $user->canAccessTransaction($request->transaction)) {
            return false;
        }

        return DB::transaction(function () use ($user, $request, $confirm, $notes) {
            $locked = LendingRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== LendingRequestStatus::PendingHandover) {
                return false;
            }

            $transaction = Transaction::query()
                ->whereKey($locked->transaction_id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return false;
            }

            if ($confirm) {
                $locked->update([
                    'status' => LendingRequestStatus::OnLoan,
                    'handed_over_by' => $user->id,
                    'handed_over_at' => now(),
                    'handover_notes' => $notes,
                ]);

                $transaction->update([
                    'lending_status' => TransactionLendingStatus::OnLoan,
                    'active_lending_request_id' => $locked->id,
                ]);

                $this->recordHistory(
                    $locked,
                    LendingRequestAction::HandoverConfirmed,
                    LendingRequestStatus::PendingHandover,
                    LendingRequestStatus::OnLoan,
                    $user,
                    $notes,
                );
            } else {
                $locked->update([
                    'status' => LendingRequestStatus::Rejected,
                    'handed_over_by' => $user->id,
                    'handed_over_at' => now(),
                    'handover_notes' => $notes,
                ]);

                $this->recordHistory(
                    $locked,
                    LendingRequestAction::HandoverRejected,
                    LendingRequestStatus::PendingHandover,
                    LendingRequestStatus::Rejected,
                    $user,
                    $notes,
                );
            }

            return true;
        });
    }

    public function returnDocuments(User $user, LendingRequest $request, ?string $notes = null): bool
    {
        if (! $user->hasPermission('lending-requests.handover')) {
            return false;
        }

        if ($request->status !== LendingRequestStatus::OnLoan) {
            return false;
        }

        $request->loadMissing('transaction');

        if (! $request->transaction || ! $user->canAccessTransaction($request->transaction)) {
            return false;
        }

        return DB::transaction(function () use ($user, $request, $notes) {
            $locked = LendingRequest::query()
                ->whereKey($request->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== LendingRequestStatus::OnLoan) {
                return false;
            }

            $transaction = Transaction::query()
                ->whereKey($locked->transaction_id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                return false;
            }

            $locked->update([
                'status' => LendingRequestStatus::Returned,
                'returned_by' => $user->id,
                'returned_at' => now(),
                'return_notes' => $notes,
            ]);

            $transaction->update([
                'lending_status' => TransactionLendingStatus::Available,
                'active_lending_request_id' => null,
            ]);

            $this->recordHistory(
                $locked,
                LendingRequestAction::Returned,
                LendingRequestStatus::OnLoan,
                LendingRequestStatus::Returned,
                $user,
                $notes,
            );

            return true;
        });
    }

    public function canReview(User $user, LendingRequest $request): bool
    {
        return $user->hasPermission('lending-requests.review')
            && $request->status === LendingRequestStatus::PendingReview
            && $this->canActOnRequest($user, $request);
    }

    public function canHandover(User $user, LendingRequest $request): bool
    {
        return $user->hasPermission('lending-requests.handover')
            && $request->status === LendingRequestStatus::PendingHandover
            && $this->canActOnRequest($user, $request);
    }

    public function canReturn(User $user, LendingRequest $request): bool
    {
        return $user->hasPermission('lending-requests.handover')
            && $request->status === LendingRequestStatus::OnLoan
            && $this->canActOnRequest($user, $request);
    }

    private function canActOnRequest(User $user, LendingRequest $request): bool
    {
        $request->loadMissing('transaction');

        return $request->transaction && $user->canAccessTransaction($request->transaction);
    }

    private function recordHistory(
        LendingRequest $request,
        LendingRequestAction $action,
        ?LendingRequestStatus $fromStatus,
        LendingRequestStatus $toStatus,
        User $user,
        ?string $notes = null,
    ): LendingRequestHistory {
        return $request->histories()->create([
            'action' => $action->value,
            'from_status' => $fromStatus?->value,
            'to_status' => $toStatus->value,
            'performed_by' => $user->id,
            'notes' => $notes,
        ]);
    }
}
