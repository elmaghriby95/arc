<?php

namespace App\Services;

use App\Enums\WorkflowAction;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Notifications\TransactionStatusChanged;
use Illuminate\Support\Collection;

class TransactionStatusNotificationService
{
    public function notifyFromHistory(TransactionStatusHistory $history): void
    {
        $history->loadMissing([
            'transaction.department',
            'fromStatus',
            'toStatus',
            'changedBy',
        ]);

        $transaction = $history->transaction;
        $changedBy = $history->changedBy;
        $toStatus = $history->toStatus;

        if (! $transaction || ! $changedBy || ! $toStatus) {
            return;
        }

        $action = $history->action
            ? WorkflowAction::tryFrom($history->action)
            : null;

        if ($action === WorkflowAction::Reject) {
            $this->notifyReject($transaction, $changedBy, $history->fromStatus, $toStatus);

            return;
        }

        $this->notify(
            $transaction,
            $changedBy,
            $history->fromStatus,
            $toStatus,
            $action,
        );
    }

    public function notify(
        Transaction $transaction,
        User $changedBy,
        ?TransactionStatus $fromStatus,
        TransactionStatus $toStatus,
        ?WorkflowAction $action = null,
    ): void {
        $recipients = $this->resolveForwardRecipients($transaction, $changedBy, $toStatus);

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TransactionStatusChanged(
            $transaction,
            $changedBy,
            $fromStatus,
            $toStatus,
            $action,
        );

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    private function notifyReject(
        Transaction $transaction,
        User $changedBy,
        ?TransactionStatus $fromStatus,
        TransactionStatus $toStatus,
    ): void {
        $recipients = $this->resolveStatusResponsibleUsers($transaction, $toStatus, $changedBy);

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TransactionStatusChanged(
            $transaction,
            $changedBy,
            $fromStatus,
            $toStatus,
            WorkflowAction::Reject,
        );

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /** @return Collection<int, User> */
    private function resolveForwardRecipients(
        Transaction $transaction,
        User $changedBy,
        TransactionStatus $toStatus,
    ): Collection {
        $recipients = collect();

        if ($transaction->created_by !== $changedBy->id) {
            $creator = $transaction->creator;

            if ($creator && $creator->hasPermission('transactions.view') && $creator->canAccessDepartment($transaction->department_id)) {
                $recipients->put($creator->id, $creator);
            }
        }

        $nextStatus = $toStatus->nextInWorkflow();

        if ($nextStatus?->required_permission) {
            $this->resolveStatusResponsibleUsers($transaction, $nextStatus, $changedBy)
                ->each(fn (User $user) => $recipients->put($user->id, $user));
        }

        if (! $nextStatus) {
            $head = $transaction->department?->head;

            if ($head
                && $head->id !== $changedBy->id
                && $head->hasPermission('transactions.view')
                && $head->canAccessDepartment($transaction->department_id)
            ) {
                $recipients->put($head->id, $head);
            }
        }

        return $recipients->values();
    }

    /** @return Collection<int, User> */
    private function resolveStatusResponsibleUsers(
        Transaction $transaction,
        TransactionStatus $status,
        User $exclude,
    ): Collection {
        $recipients = collect();

        if ($status->is_initial || ! $status->required_permission) {
            $creator = $transaction->creator;

            if ($creator
                && $creator->id !== $exclude->id
                && $creator->hasPermission('transactions.view')
                && $creator->canAccessDepartment($transaction->department_id)
            ) {
                $recipients->put($creator->id, $creator);
            }

            return $recipients->values();
        }

        User::query()
            ->with('role')
            ->where('id', '!=', $exclude->id)
            ->get()
            ->filter(function (User $user) use ($transaction, $status) {
                if (! $user->hasPermission('transactions.view')
                    || ! $user->hasPermission($status->required_permission)) {
                    return false;
                }

                if ($status->isGlobalScope()) {
                    return true;
                }

                return $user->canAccessDepartment($transaction->department_id);
            })
            ->each(fn (User $user) => $recipients->put($user->id, $user));

        return $recipients->values();
    }
}
