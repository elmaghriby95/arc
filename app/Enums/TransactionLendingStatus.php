<?php

namespace App\Enums;

enum TransactionLendingStatus: string
{
    case Available = 'available';
    case OnLoan = 'on_loan';

    public function label(): string
    {
        return __('lending_requests.lending_status.'.$this->value);
    }
}
