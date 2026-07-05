<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return __('documents.status.'.$this->value);
    }
}
