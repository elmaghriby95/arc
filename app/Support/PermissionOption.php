<?php

namespace App\Support;

readonly class PermissionOption
{
    public function __construct(
        public string $value,
        private string $labelText,
    ) {}

    public function label(): string
    {
        return $this->labelText;
    }
}
