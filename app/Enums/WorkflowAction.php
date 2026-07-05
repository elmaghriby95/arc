<?php

namespace App\Enums;

enum WorkflowAction: string
{
    case Create = 'create';
    case Submit = 'submit';
    case Approve = 'approve';
    case Reject = 'reject';
    case Auto = 'auto';

    public function label(): string
    {
        return __('workflow.action.'.$this->value);
    }
}
