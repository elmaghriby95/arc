<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.users.index') }}" class="settings-back-link">{{ __('common.back_to_users') }}</a>
                <h2 class="page-title">{{ __('settings.users.create_title') }}</h2>
                <p class="page-subtitle">{{ __('settings.users.create_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="user-create-steps" aria-hidden="true">
            <div class="user-create-step user-create-step--active">
                <span class="user-create-step-num">1</span>
                <span class="user-create-step-label">{{ __('settings.users.step_account') }}</span>
            </div>
            <div class="user-create-step-line"></div>
            <div class="user-create-step">
                <span class="user-create-step-num">2</span>
                <span class="user-create-step-label">{{ __('settings.users.step_role') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.users.store') }}" class="user-create-form" data-user-create-form>
            @csrf

            <div class="card user-create-card">
                <div class="card-header user-create-card-header">
                    <div class="user-create-card-icon user-create-card-icon--account">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="card-title">{{ __('settings.users.account_title') }}</h3>
                        <p class="user-create-card-desc">{{ __('settings.users.account_desc') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <x-input-label for="name" :value="__('settings.users.full_name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required :placeholder="__('settings.users.name_placeholder')" autofocus />
                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-group">
                            <x-input-label for="employee_number" :value="__('settings.users.employee_number')" />
                            <x-text-input id="employee_number" name="employee_number" type="text" :value="old('employee_number')" required :placeholder="__('settings.users.employee_number_placeholder')" dir="ltr" />
                            @error('employee_number')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-group">
                            <x-input-label for="email" :value="__('common.email')" />
                            <x-text-input id="email" name="email" type="email" :value="old('email')" required placeholder="example@domain.com" dir="ltr" />
                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <div class="form-label-row">
                                <x-input-label for="password" :value="__('auth.password')" />
                                <button type="button" class="btn btn-link btn-sm" data-generate-password>{{ __('settings.users.generate_password') }}</button>
                            </div>
                            <div class="password-input-wrap">
                                <x-text-input id="password" name="password" type="password" required autocomplete="new-password" data-password-input />
                                <button type="button" class="password-toggle" data-password-toggle :aria-label="__('auth.show_password')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-show>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-hide style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 0 1 1.563-3.029m5.858 3.293a3 3 0 1 0 4.243 4.243m-4.243-4.243L3 3m3.878 3.878L21 21"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-group">
                            <x-input-label for="password_confirmation" :value="__('auth.confirm_password')" />
                            <div class="password-input-wrap">
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" data-password-input />
                                <button type="button" class="password-toggle" data-password-toggle :aria-label="__('auth.show_password')">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-show>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" data-icon-hide style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 0 1 1.563-3.029m5.858 3.293a3 3 0 1 0 4.243 4.243m-4.243-4.243L3 3m3.878 3.878L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="form-hint">{{ __('settings.users.auto_activate_hint') }}</p>
                </div>
            </div>

            <div class="card user-create-card">
                <div class="card-header user-create-card-header">
                    <div class="user-create-card-icon user-create-card-icon--role">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="card-title">{{ __('settings.users.role_org_title') }}</h3>
                        <p class="user-create-card-desc">{{ __('settings.users.role_org_desc') }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <x-input-label for="role_id" :value="__('common.role')" />
                            @if ($canAssignUserRole)
                                <select id="role_id" name="role_id" class="form-select" required data-role-select>
                                    <option value="" disabled @selected(! old('role_id'))>{{ __('settings.users.choose_role') }}</option>
                                    @foreach ($roles as $role)
                                        <option
                                            value="{{ $role->id }}"
                                            data-slug="{{ $role->slug }}"
                                            data-description="{{ $role->description }}"
                                            @selected(old('role_id') == $role->id)
                                            @if ($role->slug === 'admin' && ! auth()->user()->isAdmin()) disabled @endif
                                        >{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="role_id" value="{{ $defaultRole?->id }}">
                                <div class="form-readonly-value">{{ $defaultRole?->name ?? __('settings.users.default_role') }}</div>
                                <p class="form-hint">{{ __('settings.users.role_assign_permission_required') }}</p>
                            @endif
                            @error('role_id')<p class="form-error">{{ $message }}</p>@enderror

                            @if ($canAssignUserRole)
                                <div class="role-description-preview" data-role-description hidden>
                                    <span class="role-description-preview-label">{{ __('settings.users.role_description') }}</span>
                                    <span data-role-description-text></span>
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <x-input-label for="department_id" :value="__('settings.users.org_location')" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id'),
                            ])
                            @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="org-scope-preview" data-org-preview hidden>
                        <span class="org-scope-preview-label">{{ __('settings.users.org_path') }}</span>
                        <span class="org-path" data-org-preview-text></span>
                    </div>

                    <div class="org-scope-info">
                        <strong>{{ __('settings.users.scope_title') }}</strong>
                        <ul>
                            <li>{{ __('settings.users.scope_line_1') }}</li>
                            <li>{{ __('settings.users.scope_line_2') }}</li>
                            <li>{{ __('settings.users.scope_line_3') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card user-create-footer">
                <div class="card-footer form-actions">
                    <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                    <x-primary-button>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                        {{ __('settings.users.create_submit') }}
                    </x-primary-button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            window.__userCreateBreadcrumbs = @json($breadcrumbs);
        </script>
    @endpush
</x-app-layout>
