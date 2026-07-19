<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::SettingsUsersCreate->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'employee_number' => ['required', 'string', 'max:50', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.user.name_required'),
            'email.required' => __('validation.user.email_required'),
            'email.email' => __('validation.user.email_format'),
            'email.unique' => __('validation.user.email_unique'),
            'employee_number.required' => __('validation.user.employee_number_required'),
            'employee_number.unique' => __('validation.user.employee_number_unique'),
            'password.required' => __('validation.user.password_required'),
            'password.confirmed' => __('validation.user.password_confirmed'),
            'role_id.required' => __('validation.user.role_required'),
            'department_id.exists' => __('validation.user.department_exists'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = Role::find($this->input('role_id'));

            if ($role?->isSuperAdmin() && ! $this->user()?->isAdmin()) {
                $validator->errors()->add('role_id', __('validation.user.cannot_assign_admin'));
            }
        });
    }

}
