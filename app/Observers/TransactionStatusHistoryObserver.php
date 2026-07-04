<?php

namespace App\Observers;

use App\Models\TransactionStatusHistory;
use App\Services\TransactionStatusNotificationService;

class TransactionStatusHistoryObserver
{
    public function __construct(
        private TransactionStatusNotificationService $notificationService,
    ) {}

    public function created(TransactionStatusHistory $history): void
    {
        $this->notificationService->notifyFromHistory($history);
    }
}
