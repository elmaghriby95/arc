@props(['permissionGroups', 'selected' => []])

<div class="permissions-form">
    <div class="permissions-form-toolbar">
        <p class="permissions-form-hint">{{ __('settings.roles.permissions_hint') }}</p>
        <div class="permissions-form-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-permissions-select-all>{{ __('settings.roles.select_all') }}</button>
            <button type="button" class="btn btn-secondary btn-sm" data-permissions-clear-all>{{ __('settings.roles.clear_all') }}</button>
        </div>
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
                        <label class="permission-checkbox">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->value }}"
                                @checked(in_array($permission->value, old('permissions', $selected), true))
                            >
                            <span class="permission-checkbox-label">{{ $permission->label() }}</span>
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
</div>
