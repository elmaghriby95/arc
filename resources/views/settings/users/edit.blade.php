<x-app-layout>
    @push('styles')
        <x-inline-css file="profile.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.users.index') }}" class="settings-back-link">{{ __('common.back_to_users') }}</a>
                <h1 class="page-title">{{ __('settings.users.edit_title', ['name' => $user->name]) }}</h1>
                <p class="page-subtitle">{{ __('settings.users.edit_subtitle_full') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="profile-page">
        <div class="profile-hero">
            <div class="profile-hero-bg"></div>
            <div class="profile-hero-content">
                <div class="profile-hero-info">
                    <div class="profile-hero-name-row">
                        <h2 class="profile-hero-name">{{ $user->name }}</h2>
                        @if ($user->is(auth()->user()))
                            <span class="user-tag">{{ __('settings.users.you') }}</span>
                        @endif
                    </div>
                    <p class="profile-hero-email">{{ $user->email }}</p>

                    <div class="profile-hero-meta">
                        @if ($orgBreadcrumb)
                            <span class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/></svg>
                                <span class="org-path">{{ $orgBreadcrumb }}</span>
                            </span>
                        @endif

                        @if ($user->last_login_at)
                            <span class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                {{ __('profile.last_login') }}: {{ $user->last_login_at->diffForHumans() }}
                            </span>
                        @else
                            <span class="profile-meta-item profile-meta-item--muted">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                {{ __('profile.no_login_yet') }}
                            </span>
                        @endif

                        <span class="profile-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                            {{ __('profile.member_since') }}: {{ $user->created_at->translatedFormat('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-stats-grid">
            <div class="profile-stat-card profile-stat-card--indigo">
                <span class="profile-stat-value">{{ $stats['transactions'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.transactions') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--cyan">
                <span class="profile-stat-value">{{ $stats['documents'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.documents') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--violet">
                <span class="profile-stat-value">{{ $stats['workflow_actions'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.workflow') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--emerald">
                <span class="profile-stat-value">{{ $stats['audit_entries'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.audit') }}</span>
            </div>
        </div>

        <div class="profile-layout">
            <div class="profile-main">
                <div class="card card-elevated">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('profile.org_data') }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="profile-info-grid">
                            <div class="profile-info-item">
                                <dt>{{ __('profile.employee_number') }}</dt>
                                <dd>{{ $user->employee_number ?: '—' }}</dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('common.role') }}</dt>
                                <dd><span class="role-pill role-pill--{{ $user->roleSlug() }}">{{ $user->role?->name ?? '—' }}</span></dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.department') }}</dt>
                                <dd>
                                    @if ($orgBreadcrumb)
                                        <span class="org-path">{{ $orgBreadcrumb }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.language') }}</dt>
                                <dd>{{ $user->language?->name ?? __('profile.default_language') }}</dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.email_status') }}</dt>
                                <dd>
                                    @if ($user->hasVerifiedEmail())
                                        <span class="profile-status-badge profile-status-badge--success">{{ __('profile.email_verified') }}</span>
                                    @else
                                        <span class="profile-status-badge profile-status-badge--warning">{{ __('profile.email_unverified') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.account_created') }}</dt>
                                <dd>{{ $user->created_at->translatedFormat('l, d F Y — H:i') }}</dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.last_updated') }}</dt>
                                <dd>{{ $user->updated_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <form id="user-update-form" method="POST" action="{{ route('settings.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="profile-tabs" data-profile-tabs>
                        <div class="profile-tabs-nav">
                            <button type="button" class="profile-tab-btn is-active" data-tab="info">{{ __('settings.users.tab.info') }}</button>
                            <button type="button" class="profile-tab-btn" data-tab="organization">{{ __('settings.users.role_org_title') }}</button>
                            <button type="button" class="profile-tab-btn" data-tab="password">{{ __('settings.users.tab.password') }}</button>
                        </div>

                        <div class="profile-tab-panel is-active" data-panel="info">
                            <div class="card">
                                <div class="card-body">
                                    <header class="profile-form-header">
                                        <h2 class="card-title">{{ __('settings.users.account_title') }}</h2>
                                        <p class="text-muted">{{ __('settings.users.account_edit_desc') }}</p>
                                    </header>

                                    <div class="form-grid form-grid-2">
                                        <div class="form-group">
                                            <x-input-label for="name" :value="__('settings.users.full_name')" />
                                            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus />
                                            @error('name')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="form-group">
                                            <x-input-label for="employee_number" :value="__('settings.users.employee_number')" />
                                            <x-text-input id="employee_number" name="employee_number" type="text" :value="old('employee_number', $user->employee_number)" :placeholder="__('settings.users.employee_number_placeholder')" dir="ltr" />
                                            @error('employee_number')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="form-group">
                                            <x-input-label for="email" :value="__('common.email')" />
                                            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required />
                                            @error('email')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="form-group">
                                            <x-input-label for="language_id" :value="__('profile.language')" />
                                            <select id="language_id" name="language_id" class="form-select">
                                                <option value="">{{ __('profile.default_language') }}</option>
                                                @foreach ($languages as $language)
                                                    <option value="{{ $language->id }}" @selected(old('language_id', $user->language_id) == $language->id)>
                                                        {{ $language->name }} ({{ $language->native_name }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('language_id')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="profile-tab-panel" data-panel="organization">
                            <div class="card">
                                <div class="card-body">
                                    <header class="profile-form-header">
                                        <h2 class="card-title">{{ __('settings.users.role_org_title') }}</h2>
                                        <p class="text-muted">{{ __('settings.users.role_org_desc') }}</p>
                                    </header>

                                    <div class="form-grid form-grid-2">
                                        <div class="form-group">
                                            <x-input-label for="role_id" :value="__('common.role')" />
                                            <select id="role_id" name="role_id" class="form-select" required>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role_id')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="form-group">
                                            <x-input-label for="department_id" :value="__('settings.users.org_location')" />
                                            @include('settings.partials.org-unit-select', [
                                                'orgUnits' => $orgUnits,
                                                'selected' => old('department_id', $user->department_id),
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

                                    <div class="form-group">
                                        <label class="inline-flex items-center gap-2" for="view_descendant_units">
                                            <input type="checkbox" id="view_descendant_units" name="view_descendant_units" value="1" @checked(in_array(old('view_descendant_units', $user->view_descendant_units ? '1' : '0'), [true, 1, '1'], true))>
                                            <span>{{ __('settings.users.view_descendant_units') }}</span>
                                        </label>
                                        <p class="form-hint">{{ __('settings.users.view_descendant_units_hint') }}</p>
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
                        </div>

                        <div class="profile-tab-panel" data-panel="password">
                            <div class="card">
                                <div class="card-body">
                                    <header class="profile-form-header">
                                        <h2 class="card-title">{{ __('settings.users.password_title') }}</h2>
                                        <p class="text-muted">{{ __('settings.users.password_edit_desc') }}</p>
                                    </header>

                                    <div class="form-grid form-grid-2">
                                        <div class="form-group">
                                            <x-input-label for="password" :value="__('profile.new_password')" />
                                            <x-text-input id="password" name="password" type="password" autocomplete="new-password" />
                                            @error('password')<p class="form-error">{{ $message }}</p>@enderror
                                        </div>

                                        <div class="form-group">
                                            <x-input-label for="password_confirmation" :value="__('profile.confirm_password')" />
                                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
                                        </div>
                                    </div>

                                    <p class="form-hint">{{ __('settings.users.password_optional_hint') }}</p>
                                    <p class="form-hint">{{ __('settings.users.password_force_change_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($canCustomizePermissions)
                        @include('settings.users.partials.permissions-form', [
                            'permissionGroups' => $permissionGroups,
                            'selected' => $selectedPermissions,
                            'granted' => $grantedPermissions,
                            'revoked' => $revokedPermissions,
                        ])

                        <script type="application/json" id="user-role-permissions">@json($rolePermissionMap)</script>
                    @endif

                </form>

                <div class="form-actions profile-form-actions">
                    <x-primary-button form="user-update-form">{{ __('settings.users.save_changes') }}</x-primary-button>
                    <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">{{ __('common.cancel') }}</a>
                </div>
            </div>

            <aside class="profile-sidebar">
                @include('users.partials.activity-sidebar', ['activities' => $activities])
            </aside>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-profile-tabs]').forEach(function (tabs) {
            tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.dataset.tab;
                    tabs.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    tabs.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('is-active', p.dataset.panel === tab); });
                });
            });
        });

        (function () {
            var root = document.querySelector('[data-user-permissions]');
            if (!root) return;

            var mapEl = document.getElementById('user-role-permissions');
            var map = {};
            if (mapEl) {
                try { map = JSON.parse(mapEl.textContent); } catch (e) { map = {}; }
            }
            var roleSelect = document.getElementById('role_id');
            var addedLabel = root.dataset.addedLabel || '';
            var removedLabel = root.dataset.removedLabel || '';

            function currentRolePermissions() {
                if (!roleSelect) return [];
                return map[roleSelect.value] || [];
            }

            function refreshItem(item) {
                var input = item.querySelector('[data-user-permission]');
                var badge = item.querySelector('[data-permission-badge]');
                if (!input || !badge) return;

                var inRole = currentRolePermissions().indexOf(input.value) !== -1;
                badge.classList.remove('permission-checkbox-badge--added', 'permission-checkbox-badge--removed');

                if (input.checked && !inRole) {
                    badge.hidden = false;
                    badge.textContent = addedLabel;
                    badge.classList.add('permission-checkbox-badge--added');
                } else if (!input.checked && inRole) {
                    badge.hidden = false;
                    badge.textContent = removedLabel;
                    badge.classList.add('permission-checkbox-badge--removed');
                } else {
                    badge.hidden = true;
                    badge.textContent = '';
                }
            }

            function refreshAll() {
                root.querySelectorAll('[data-user-permission-item]').forEach(refreshItem);
            }

            root.querySelectorAll('[data-user-permission]').forEach(function (input) {
                input.addEventListener('change', function () {
                    refreshItem(input.closest('[data-user-permission-item]'));
                });
            });

            if (roleSelect) {
                roleSelect.addEventListener('change', function () {
                    var perms = currentRolePermissions();
                    root.querySelectorAll('[data-user-permission]').forEach(function (input) {
                        input.checked = perms.indexOf(input.value) !== -1;
                    });
                    refreshAll();
                });
            }

            var selectAll = root.querySelector('[data-permissions-select-all]');
            if (selectAll) {
                selectAll.addEventListener('click', function () {
                    root.querySelectorAll('[data-user-permission-item]').forEach(function (item) {
                        if (item.hidden) return;
                        var input = item.querySelector('[data-user-permission]');
                        if (input) input.checked = true;
                    });
                    refreshAll();
                });
            }

            var clearAll = root.querySelector('[data-permissions-clear-all]');
            if (clearAll) {
                clearAll.addEventListener('click', function () {
                    root.querySelectorAll('[data-user-permission-item]').forEach(function (item) {
                        if (item.hidden) return;
                        var input = item.querySelector('[data-user-permission]');
                        if (input) input.checked = false;
                    });
                    refreshAll();
                });
            }

            root.querySelectorAll('[data-permission-group-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var group = btn.closest('[data-permission-group]');
                    var inputs = Array.from(group.querySelectorAll('[data-user-permission]'));
                    var allChecked = inputs.every(function (input) { return input.checked; });
                    inputs.forEach(function (input) { input.checked = !allChecked; });
                    refreshAll();
                });
            });

            var search = root.querySelector('[data-user-permission-search]');
            if (search) {
                search.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') event.preventDefault();
                });
                search.addEventListener('input', function () {
                    var q = search.value.trim().toLowerCase();
                    root.querySelectorAll('[data-permission-group]').forEach(function (group) {
                        var visible = 0;
                        group.querySelectorAll('[data-user-permission-item]').forEach(function (item) {
                            var match = q === '' || item.textContent.toLowerCase().indexOf(q) !== -1;
                            item.hidden = !match;
                            if (match) visible += 1;
                        });
                        group.hidden = visible === 0;
                    });
                });
            }

            refreshAll();
        })();
    </script>
    @endpush
</x-app-layout>
