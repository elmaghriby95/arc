<?php

namespace App\Services;

use App\Enums\WorkflowAction;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Notifications\TransactionStatusChanged;
use Illuminate\Notifications\DatabaseNotification;
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
        if ($toStatus->is_final) {
            return $this->resolveCompletionRecipients($transaction, $changedBy);
        }

        $recipients = $this->resolveStatusResponsibleUsers($transaction, $toStatus, $changedBy);
        $creator = $transaction->creator;

        if ($creator
            && $creator->id !== $changedBy->id
            && ! $this->handlesWorkflowStage($creator)
            && $this->isCreatorRecipient($creator, $transaction)
        ) {
            $recipients->put($creator->id, $creator);
        }

        return $recipients;
    }

    /** @return Collection<int, User> */
    private function resolveCompletionRecipients(Transaction $transaction, User $changedBy): Collection
    {
        $recipients = collect();
        $transaction->loadMissing(['creator', 'department.head']);

        $creator = $transaction->creator;

        if ($creator && $creator->id !== $changedBy->id && $this->isCompletionRecipient($creator, $transaction)) {
            $recipients->put($creator->id, $creator);
        }

        $head = $transaction->department?->head;

        if ($head && $head->id !== $changedBy->id && $this->isCompletionRecipient($head, $transaction)) {
            $recipients->put($head->id, $head);
        }

        return $recipients->values();
    }

    /**
     * Hide stage notifications that were stored for a user who does not handle that stage.
     *
     * @param  iterable<int, DatabaseNotification>  $notifications
     * @return Collection<int, DatabaseNotification>
     */
    public function filterVisible(User $user, iterable $notifications): Collection
    {
        $notifications = collect($notifications);
        $transactionIds = $notifications
            ->map(fn (DatabaseNotification $notification) => $notification->data['transaction_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $transactions = Transaction::query()
            ->with('department')
            ->whereIn('id', $transactionIds)
            ->get()
            ->keyBy('id');

        $statusesByName = TransactionStatus::query()->get()->keyBy('name');

        return $notifications
            ->filter(function (DatabaseNotification $notification) use ($user, $transactions, $statusesByName) {
                return $this->notificationIsVisible(
                    $user,
                    $notification->data,
                    $transactions,
                    $statusesByName,
                );
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int|string, Transaction>  $transactions
     * @param  Collection<string, TransactionStatus>  $statusesByName
     */
    private function notificationIsVisible(
        User $user,
        array $data,
        Collection $transactions,
        Collection $statusesByName,
    ): bool {
        if (($data['type'] ?? null) !== 'transaction_status_changed') {
            return true;
        }

        $status = $statusesByName->get($data['to_status'] ?? '');
        $transaction = $transactions->get($data['transaction_id'] ?? 0);

        if (! $status || ! $transaction) {
            return false;
        }

        if ($status->is_final) {
            return $this->isCompletionRecipient($user, $transaction);
        }

        if ($status->is_initial || ! $status->required_permission) {
            return $this->isCreatorRecipient($user, $transaction);
        }

        if ($this->userCanActOnStatus($user, $transaction, $status)) {
            return true;
        }

        return ! $this->handlesWorkflowStage($user) && $this->isCreatorRecipient($user, $transaction);
    }

    /** @var array<int, bool> */
    private array $workflowStageHandlers = [];

    private function handlesWorkflowStage(User $user): bool
    {
        if (array_key_exists($user->id, $this->workflowStageHandlers)) {
            return $this->workflowStageHandlers[$user->id];
        }

        $handles = TransactionStatus::query()
            ->where('is_active', true)
            ->where('is_initial', false)
            ->whereNotNull('required_permission')
            ->get(['required_permission'])
            ->contains(fn (TransactionStatus $status) => $user->hasPermission($status->required_permission));

        return $this->workflowStageHandlers[$user->id] = $handles;
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

            if ($creator && $creator->id !== $exclude->id && $this->isCreatorRecipient($creator, $transaction)) {
                $recipients->put($creator->id, $creator);
            }

            return $recipients->values();
        }

        User::query()
            ->with('role')
            ->where('id', '!=', $exclude->id)
            ->get()
            ->filter(fn (User $user) => $this->userCanActOnStatus($user, $transaction, $status))
            ->each(fn (User $user) => $recipients->put($user->id, $user));

        return $recipients->values();
    }

    private function userCanActOnStatus(User $user, Transaction $transaction, TransactionStatus $status): bool
    {
        if (! $user->hasPermission('transactions.view')
            || ! $status->required_permission
            || ! $user->hasPermission($status->required_permission)) {
            return false;
        }

        if ($status->isGlobalScope()) {
            return true;
        }

        return $user->canAccessDepartment($transaction->department_id);
    }

    private function isCreatorRecipient(User $user, Transaction $transaction): bool
    {
        return (int) $transaction->created_by === (int) $user->id
            && $user->hasPermission('transactions.view')
            && $user->canAccessDepartment($transaction->department_id);
    }

    private function isCompletionRecipient(User $user, Transaction $transaction): bool
    {
        if (! $user->hasPermission('transactions.view') || ! $user->canAccessDepartment($transaction->department_id)) {
            return false;
        }

        $transaction->loadMissing('department');

        return (int) $transaction->created_by === (int) $user->id
            || (int) ($transaction->department?->head_id ?? 0) === (int) $user->id;
    }
}
