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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role_id' => ['required', Rule::exists('roles', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المستخدم مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'role_id.required' => 'يجب اختيار دور للمستخدم.',
            'department_id.exists' => 'الوحدة التنظيمية المحددة غير موجودة.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $role = Role::find($this->input('role_id'));

            if ($role && $role->slug === 'admin' && ! $this->user()?->isAdmin()) {
                $validator->errors()->add('role_id', 'لا يمكنك تعيين دور مدير النظام.');
            }
        });
    }
}
