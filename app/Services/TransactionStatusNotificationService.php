<?php

namespace App\Services;

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

        $this->notify(
            $transaction,
            $changedBy,
            $history->fromStatus,
            $toStatus,
        );
    }

    public function notify(
        Transaction $transaction,
        User $changedBy,
        ?TransactionStatus $fromStatus,
        TransactionStatus $toStatus,
    ): void {
        $recipients = $this->resolveRecipients($transaction, $changedBy, $toStatus);

        if ($recipients->isEmpty()) {
            return;
        }

        $notification = new TransactionStatusChanged(
            $transaction,
            $changedBy,
            $fromStatus,
            $toStatus,
        );

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /** @return Collection<int, User> */
    private function resolveRecipients(
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
            User::query()
                ->with('role')
                ->where('id', '!=', $changedBy->id)
                ->get()
                ->filter(function (User $user) use ($transaction, $nextStatus) {
                    return $user->hasPermission('transactions.view')
                        && $user->hasPermission($nextStatus->required_permission)
                        && $user->canAccessDepartment($transaction->department_id);
                })
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
}
