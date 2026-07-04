<?php

namespace App\Notifications;

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

        if ($fromName === null) {
            $message = sprintf(
                'تم إنشاء معاملة جديدة «%s» بحالة %s',
                $this->transaction->title,
                $toName,
            );
        } else {
            $message = sprintf(
                'تم تحديث حالة المعاملة «%s» من %s إلى %s بواسطة %s',
                $this->transaction->title,
                $fromName,
                $toName,
                $this->changedBy->name,
            );
        }

        return [
            'type' => 'transaction_status_changed',
            'message' => $message,
            'transaction_id' => $this->transaction->id,
            'reference_number' => $this->transaction->reference_number,
            'from_status' => $fromName,
            'to_status' => $toName,
            'to_status_color' => $this->toStatus->color,
            'changed_by' => $this->changedBy->name,
            'url' => route('transactions.show', $this->transaction, absolute: false),
        ];
    }
}
