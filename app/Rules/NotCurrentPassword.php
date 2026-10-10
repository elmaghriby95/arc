<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotCurrentPassword implements ValidationRule
{
    public function __construct(private readonly User $user) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (Hash::check($value, $this->user->password)) {
            $fail(__('auth.password_reused'));
        }
    }
}
