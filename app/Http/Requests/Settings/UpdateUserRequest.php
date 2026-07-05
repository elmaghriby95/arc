<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::SettingsUsersEdit->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
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
        });
    }
}
