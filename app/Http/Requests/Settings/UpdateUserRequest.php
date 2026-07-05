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

            if ($user && $user->is($this->user()) && $role && $role->slug !== 'admin') {
                $validator->errors()->add('role_id', __('validation.user.cannot_demote_self'));
            }

            if ($role && $role->slug === 'admin' && ! $this->user()?->isAdmin()) {
                $validator->errors()->add('role_id', __('validation.user.cannot_assign_admin'));
            }
        });
    }
}
