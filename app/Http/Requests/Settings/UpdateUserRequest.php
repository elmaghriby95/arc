<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::SettingsUsersEdit->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'employee_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'language_id' => ['nullable', Rule::exists('languages', 'id')],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
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
            'password.confirmed' => __('validation.user.password_confirmed'),
            'role_id.required' => __('validation.user.role_required'),
            'department_id.exists' => __('validation.user.department_exists'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');
            $role = Role::find($this->input('role_id'));

            if ($user?->isAdmin() && $role && ! $role->isSuperAdmin()) {
                $validator->errors()->add('role_id', __('validation.user.cannot_change_super_admin_role'));
            }

            if ($user && $user->is($this->user()) && $role && ! $role->isSuperAdmin()) {
                $validator->errors()->add('role_id', __('validation.user.cannot_demote_self'));
            }

            if ($role && $role->slug === 'admin' && ! $this->user()?->isAdmin()) {
                $validator->errors()->add('role_id', __('validation.user.cannot_assign_admin'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('employee_number') === '') {
            $this->merge(['employee_number' => null]);
        }
    }
}
