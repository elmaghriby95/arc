<?php

namespace App\Enums;

enum LendingRequestStatus: string
{
    case PendingReview = 'pending_review';
    case PendingHandover = 'pending_handover';
    case OnLoan = 'on_loan';
    case Returned = 'returned';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('lending_requests.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingReview => '#f59e0b',
            self::PendingHandover => '#3b82f6',
            self::OnLoan => '#8b5cf6',
            self::Returned => '#10b981',
            self::Rejected => '#ef4444',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PendingReview, self::PendingHandover, self::OnLoan], true);
    }

    /** @return list<self> */
    public static function activeCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $status) => $status->isActive(),
        ));
    }
}
