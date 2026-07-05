<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.roles.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.roles.subtitle') }}</p>
            </div>
            @permission('settings.roles.create')
                <a href="{{ route('settings.roles.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    {{ __('settings.roles.create_button') }}
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="roles-grid">
            @foreach ($roles as $role)
                <div class="role-card role-card--{{ $role->slug }}">
                    <div class="role-card-header">
                        <div class="role-card-icon">
                            @if ($role->slug === 'admin')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                                </svg>
                            @elseif ($role->slug === 'manager')
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            @endif
                        </div>
                        <div class="role-card-meta">
                            <h3 class="role-card-title">{{ $role->name }}</h3>
                            <span class="role-card-count">{{ __('settings.roles.users_count', ['count' => $role->users_count]) }}</span>
                            @if ($role->is_system)
                                <span class="role-system-badge">{{ __('settings.roles.system_badge') }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($role->description)
                        <p class="role-card-desc">{{ $role->description }}</p>
                    @endif

                    <div class="role-card-permissions">
                        <h4 class="role-card-permissions-title">{{ __('settings.roles.permissions_count', ['count' => count($role->permissions ?? [])]) }}</h4>
                        <ul class="role-permissions-list">
                            @foreach (collect($role->permissions ?? [])->take(8) as $permissionKey)
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    {{ \App\Support\PermissionRegistry::labelFor($permissionKey) }}
                                </li>
                            @endforeach
                            @if (count($role->permissions ?? []) > 8)
                                <li class="role-permissions-more">{{ __('settings.roles.more_permissions', ['count' => count($role->permissions) - 8]) }}</li>
                            @endif
                        </ul>
                    </div>

                    <div class="role-card-footer">
                        @permission('settings.roles.edit')
                            <a href="{{ route('settings.roles.edit', $role) }}" class="btn btn-secondary btn-sm">{{ __('settings.roles.edit_permissions') }}</a>
                        @endpermission

                        @if (! $role->is_system)
                            @permission('settings.roles.delete')
                                <form method="POST" action="{{ route('settings.roles.destroy', $role) }}" onsubmit="return confirm(@json(__('settings.roles.confirm_delete')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('common.delete') }}</button>
                                </form>
                            @endpermission
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
