<?php

namespace App\Enums;

enum LendingRequestAction: string
{
    case Requested = 'requested';
    case ReviewApproved = 'review_approved';
    case ReviewRejected = 'review_rejected';
    case HandoverConfirmed = 'handover_confirmed';
    case HandoverRejected = 'handover_rejected';
    case Returned = 'returned';

    public function label(): string
    {
        return __('lending_requests.actions.'.$this->value);
    }
}
