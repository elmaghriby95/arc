<?php

namespace App\Observers;

use App\Models\LendingRequestHistory;
use App\Services\LendingNotificationService;

class LendingRequestHistoryObserver
{
    public function __construct(
        private LendingNotificationService $notificationService,
    ) {}

    public function created(LendingRequestHistory $history): void
    {
        $this->notificationService->notifyFromHistory($history);
    }
}
