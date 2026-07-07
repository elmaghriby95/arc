<?php

namespace App\Notifications;

use App\Enums\LendingRequestAction;
use App\Enums\LendingRequestStatus;
use App\Models\LendingRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LendingRequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public LendingRequest $lendingRequest,
        public User $performedBy,
        public ?LendingRequestStatus $fromStatus,
        public LendingRequestStatus $toStatus,
        public LendingRequestAction $action,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $this->lendingRequest->loadMissing('transaction');

        $transaction = $this->lendingRequest->transaction;
        $referenceNumber = $transaction?->reference_number ?? '—';
        $title = $transaction?->title ?? '—';

        $message = match ($this->action) {
            LendingRequestAction::Requested => __('notifications.lending.requested', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
            LendingRequestAction::ReviewApproved => __('notifications.lending.review_approved', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
            LendingRequestAction::ReviewRejected => __('notifications.lending.review_rejected', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
            LendingRequestAction::HandoverConfirmed => __('notifications.lending.handover_confirmed', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
            LendingRequestAction::HandoverRejected => __('notifications.lending.handover_rejected', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
            LendingRequestAction::Returned => __('notifications.lending.returned', [
                'reference' => $referenceNumber,
                'title' => $title,
                'by' => $this->performedBy->name,
            ]),
        };

        return [
            'type' => 'lending_request_status_changed',
            'message' => $message,
            'lending_request_id' => $this->lendingRequest->id,
            'transaction_id' => $transaction?->id,
            'reference_number' => $referenceNumber,
            'from_status' => $this->fromStatus?->label(),
            'to_status' => $this->toStatus->label(),
            'to_status_color' => $this->toStatus->color(),
            'action' => $this->action->value,
            'changed_by' => $this->performedBy->name,
            'url' => route('lending-requests.show', $this->lendingRequest, absolute: false),
        ];
    }
}
