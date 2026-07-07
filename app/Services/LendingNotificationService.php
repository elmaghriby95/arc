<?php

namespace App\Services;

use App\Enums\LendingRequestAction;
use App\Enums\LendingRequestStatus;
use App\Models\LendingRequestHistory;
use App\Models\User;
use App\Notifications\LendingRequestStatusChanged;
use Illuminate\Support\Collection;

class LendingNotificationService
{
    public function notifyFromHistory(LendingRequestHistory $history): void
    {
        $history->loadMissing([
            'lendingRequest.transaction.department',
            'performer',
        ]);

        $request = $history->lendingRequest;
        $performer = $history->performer;

        if (! $request || ! $performer) {
            return;
        }

        $recipients = $this->resolveRecipients($request, $history->action, $performer);

        if ($recipients->isEmpty()) {
            return;
        }

        $toStatus = $history->toStatusEnum();
        $action = $history->action instanceof LendingRequestAction
            ? $history->action
            : LendingRequestAction::tryFrom((string) $history->action);

        if (! $toStatus || ! $action) {
            return;
        }

        $notification = new LendingRequestStatusChanged(
            $request,
            $performer,
            $history->fromStatusEnum(),
            $toStatus,
            $action,
        );

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /** @return Collection<int, User> */
    private function resolveRecipients(
        \App\Models\LendingRequest $request,
        LendingRequestAction $action,
        User $performer,
    ): Collection {
        $request->loadMissing('transaction');

        $transaction = $request->transaction;

        if (! $transaction) {
            return collect();
        }

        return match ($action) {
            LendingRequestAction::Requested => $this->usersWithPermissionInDepartment(
                'lending-requests.review',
                $transaction->department_id,
                $performer,
            ),
            LendingRequestAction::ReviewApproved => $this->usersWithPermissionInDepartment(
                'lending-requests.handover',
                $transaction->department_id,
                $performer,
            ),
            LendingRequestAction::ReviewRejected,
            LendingRequestAction::HandoverRejected => $this->notifyRequester($request, $performer),
            LendingRequestAction::HandoverConfirmed => $this->handoverConfirmedRecipients($request, $performer, $transaction->department_id),
            LendingRequestAction::Returned => $this->returnRecipients($request, $performer, $transaction->department_id),
        };
    }

    /** @return Collection<int, User> */
    private function handoverConfirmedRecipients(
        \App\Models\LendingRequest $request,
        User $performer,
        int $departmentId,
    ): Collection {
        $recipients = $this->notifyRequester($request, $performer);

        $this->usersWithPermissionInDepartment('lending-requests.review', $departmentId, $performer)
            ->each(fn (User $user) => $recipients->put($user->id, $user));

        return $recipients->values();
    }

    /** @return Collection<int, User> */
    private function returnRecipients(
        \App\Models\LendingRequest $request,
        User $performer,
        int $departmentId,
    ): Collection {
        $recipients = $this->notifyRequester($request, $performer);

        $this->usersWithPermissionInDepartment('lending-requests.view', $departmentId, $performer)
            ->each(fn (User $user) => $recipients->put($user->id, $user));

        return $recipients->values();
    }

    /** @return Collection<int, User> */
    private function notifyRequester(\App\Models\LendingRequest $request, User $exclude): Collection
    {
        $recipients = collect();
        $requester = $request->requester;

        if ($requester && $requester->id !== $exclude->id) {
            $recipients->put($requester->id, $requester);
        }

        return $recipients->values();
    }

    /** @return Collection<int, User> */
    private function usersWithPermissionInDepartment(
        string $permission,
        int $departmentId,
        User $exclude,
    ): Collection {
        $recipients = collect();

        User::query()
            ->with('role')
            ->where('id', '!=', $exclude->id)
            ->get()
            ->filter(function (User $user) use ($permission, $departmentId) {
                if (! $user->hasPermission($permission)) {
                    return false;
                }

                if ($user->hasPermission('transactions.view-all')) {
                    return true;
                }

                return $user->canAccessDepartment($departmentId);
            })
            ->each(fn (User $user) => $recipients->put($user->id, $user));

        return $recipients->values();
    }
}
