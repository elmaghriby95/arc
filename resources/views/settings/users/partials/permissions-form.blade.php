@props([
    'permissionGroups',
    'selected' => [],
    'granted' => [],
    'revoked' => [],
])

<div
    class="card user-permissions-card"
    data-user-permissions
    data-added-label="{{ __('settings.users.permission_added') }}"
    data-removed-label="{{ __('settings.users.permission_revoked') }}"
>
    <div class="card-header">
        <h3 class="card-title">{{ __('settings.users.permissions_title') }}</h3>
    </div>
    <div class="card-body">
        <input type="hidden" name="permissions_submitted" value="1">

        <div class="permissions-form">
            <div class="permissions-form-toolbar">
                <div>
                    <p class="permissions-form-hint">{{ __('settings.users.permissions_hint') }}</p>
                    <p class="permissions-form-hint">{{ __('settings.users.permissions_role_hint') }}</p>
                </div>
                <div class="permissions-form-actions">
                    <button type="button" class="btn btn-secondary btn-sm" data-permissions-select-all>{{ __('settings.roles.select_all') }}</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-permissions-clear-all>{{ __('settings.roles.clear_all') }}</button>
                </div>
            </div>

            <div class="form-group user-permissions-search">
                <input
                    type="search"
                    class="form-control"
                    data-user-permission-search
                    placeholder="{{ __('settings.users.permissions_search') }}"
                    autocomplete="off"
                >
            </div>

            <div class="permissions-groups">
                @foreach ($permissionGroups as $group => $permissions)
                    <div class="permissions-group" data-permission-group>
                        <div class="permissions-group-header">
                            <h4 class="permissions-group-title">{{ $group }}</h4>
                            <button type="button" class="btn btn-link btn-sm" data-permission-group-toggle>{{ __('settings.roles.select_group') }}</button>
                        </div>
                        <div class="permissions-group-items">
                            @foreach ($permissions as $permission)
                                @php
                                    $isGranted = in_array($permission->value, $granted, true);
                                    $isRevoked = in_array($permission->value, $revoked, true);
                                @endphp
                                <label class="permission-checkbox" data-user-permission-item>
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->value }}"
                                        data-user-permission
                                        @checked(in_array($permission->value, $selected, true))
                                    >
                                    <span class="permission-checkbox-label">
                                        {{ $permission->label() }}
                                        <span
                                            class="permission-checkbox-badge {{ $isGranted ? 'permission-checkbox-badge--added' : ($isRevoked ? 'permission-checkbox-badge--removed' : '') }}"
                                            data-permission-badge
                                            @unless ($isGranted || $isRevoked) hidden @endunless
                                        >{{ $isGranted ? __('settings.users.permission_added') : ($isRevoked ? __('settings.users.permission_revoked') : '') }}</span>
                                    </span>
                                    <span class="permission-checkbox-key">{{ $permission->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            @error('permissions')
                <p class="form-error">{{ $message }}</p>
            @enderror
            @error('permissions.*')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
