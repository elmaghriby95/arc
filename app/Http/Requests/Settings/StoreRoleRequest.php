<?php

namespace App\Http\Requests\Settings;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Permission::SettingsRolesCreate->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(Permission::values())],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم الدور مطلوب.',
            'permissions.required' => 'يجب تحديد صلاحية واحدة على الأقل.',
            'permissions.min' => 'يجب تحديد صلاحية واحدة على الأقل.',
        ];
    }
}
