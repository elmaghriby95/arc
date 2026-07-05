<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.users.index') }}" class="settings-back-link">{{ __('common.back_to_users') }}</a>
                <h2 class="page-title">{{ __('settings.users.edit_title', ['name' => $user->name]) }}</h2>
                <p class="page-subtitle">{{ __('settings.users.edit_subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.users.user_data') }}</h3>
            </div>
            <div class="card-body">
                <div class="user-edit-summary">
                    <span class="user-avatar user-avatar--{{ $user->role?->slug ?? 'user' }}">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div>
                        <strong>{{ $user->name }}</strong>
                        <p class="text-muted">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('settings.users.update', $user) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.users.role_org_title') }}</h3>
            </div>
            <div class="card-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <x-input-label for="role_id" :value="__('common.role')" />
                        <select id="role_id" name="role_id" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-group">
                        <x-input-label for="department_id" :value="__('settings.users.org_location')" />
                        @include('settings.partials.org-unit-select', [
                            'orgUnits' => $orgUnits,
                            'selected' => $user->department_id,
                        ])
                        @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if ($user->department_id && isset($breadcrumbs[$user->department_id]))
                    <div class="org-scope-preview">
                        <span class="org-scope-preview-label">{{ __('settings.users.current_path') }}</span>
                        <span class="org-path">{{ $breadcrumbs[$user->department_id] }}</span>
                    </div>
                @endif

                <div class="org-scope-info">
                    <strong>{{ __('settings.users.scope_title') }}</strong>
                    <ul>
                        <li>{{ __('settings.users.scope_line_1') }}</li>
                        <li>{{ __('settings.users.scope_line_2') }}</li>
                        <li>{{ __('settings.users.scope_line_3') }}</li>
                    </ul>
                </div>
            </div>
            <div class="card-footer">
                <x-primary-button>{{ __('settings.users.save_changes') }}</x-primary-button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
