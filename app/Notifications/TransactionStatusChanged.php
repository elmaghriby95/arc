<?php

namespace App\Notifications;

use App\Enums\WorkflowAction;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TransactionStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public User $changedBy,
        public ?TransactionStatus $fromStatus,
        public TransactionStatus $toStatus,
        public ?WorkflowAction $action = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $fromName = $this->fromStatus?->name;
        $toName = $this->toStatus->name;

        if ($this->action === WorkflowAction::Reject && $fromName !== null) {
            $message = __('notifications.transaction.rejected', [
                'title' => $this->transaction->title,
                'from' => $fromName,
                'to' => $toName,
                'by' => $this->changedBy->name,
            ]);
        } elseif ($fromName === null) {
            $message = __('notifications.transaction.created', [
                'title' => $this->transaction->title,
                'status' => $toName,
            ]);
        } else {
            $message = __('notifications.transaction.status_updated', [
                'title' => $this->transaction->title,
                'from' => $fromName,
                'to' => $toName,
                'by' => $this->changedBy->name,
            ]);
        }

        return [
            'type' => 'transaction_status_changed',
            'message' => $message,
            'transaction_id' => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'from_status' => $fromName,
            'to_status' => $toName,
            'to_status_color' => $this->toStatus->color,
            'action' => $this->action?->value,
            'changed_by' => $this->changedBy->name,
            'url' => route('transactions.show', $this->transaction, absolute: false),
        ];
    }
}
