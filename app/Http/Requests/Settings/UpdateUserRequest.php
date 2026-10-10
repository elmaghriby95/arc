<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
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
            'view_descendant_units' => ['boolean'],
            'language_id' => ['nullable', Rule::exists('languages', 'id')],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'permissions_submitted' => ['sometimes', 'boolean'],
            'permissions' => ['exclude_unless:permissions_submitted,1', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionRegistry::allValues())],
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
            'permissions.*.in' => __('validation.user.permission_invalid'),
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

            if (
                $user
                && $user->is($this->user())
                && $role
                && (int) $role->id !== (int) $user->role_id
            ) {
                $validator->errors()->add('role_id', __('validation.user.cannot_demote_self'));
            }

            if (
                $role
                && $role->isSuperAdmin()
                && ! $this->user()?->isAdmin()
                && (int) $role->id !== (int) $user?->role_id
            ) {
                $validator->errors()->add('role_id', __('validation.user.cannot_assign_admin'));
            }

            if (
                $this->boolean('permissions_submitted')
                && $user
                && $user->is($this->user())
                && $role
                && (! $role->isSuperAdmin() || $this->user()?->isAdmin())
            ) {
                $permissions = $this->input('permissions', []);

                if (! is_array($permissions) || ! in_array(Permission::SettingsUsersEdit->value, $permissions, true)) {
                    $validator->errors()->add('permissions', __('validation.user.cannot_remove_own_user_edit'));
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_number' => $this->input('employee_number') === '' ? null : $this->input('employee_number'),
            'view_descendant_units' => $this->boolean('view_descendant_units'),
            'permissions' => $this->boolean('permissions_submitted') && ! $this->has('permissions')
                ? []
                : $this->input('permissions'),
        ]);
    }
}
