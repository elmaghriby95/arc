<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.txn_statuses.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.txn_statuses.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        @if ($statuses->isNotEmpty())
            <div class="card txn-workflow-preview">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.txn_statuses.workflow_path_title') }}</h3>
                    <p class="card-subtitle">{{ __('settings.txn_statuses.workflow_path_subtitle') }}</p>
                </div>
                <div class="card-body">
                    <div class="txn-workflow-steps">
                        @foreach ($statuses->where('is_active', true)->sortBy('sort_order') as $index => $status)
                            <div class="txn-workflow-step">
                                <span class="txn-workflow-step-num">{{ $index + 1 }}</span>
                                <span class="txn-status-badge" style="--txn-status-color: {{ $status->color ?? '#64748b' }}">{{ $status->name }}</span>
                                @if ($status->is_initial)
                                    <span class="settings-badge">{{ __('settings.txn_statuses.badge_initial') }}</span>
                                @endif
                                @if ($status->is_final)
                                    <span class="settings-badge settings-badge--success">{{ __('settings.txn_statuses.badge_final') }}</span>
                                @endif
                            </div>
                            @if (! $loop->last)
                                <span class="txn-workflow-arrow" aria-hidden="true">←</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @permission('settings.transaction-statuses.create')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.txn_statuses.add_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.transaction-statuses.store') }}" class="ref-form">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" :value="__('common.name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="code" :value="__('settings.txn_statuses.code')" />
                            <x-text-input id="code" name="code" type="text" :value="old('code')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="sort_order" :value="__('settings.txn_statuses.sort_order')" />
                            <x-text-input id="sort_order" name="sort_order" type="number" :value="old('sort_order', ($statuses->max('sort_order') ?? 0) + 1)" min="0" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="color" :value="__('settings.txn_statuses.color')" />
                            <x-text-input id="color" name="color" type="text" :value="old('color', '#64748b')" placeholder="#64748b" />
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group" data-status-permission-field>
                        <x-input-label :value="__('settings.txn_statuses.transition_permission')" />
                        <p class="form-hint" data-workflow-permission-preview>
                            @unless (old('is_initial'))
                                @if (old('name') || old('code'))
                                    {{ __('settings.txn_statuses.transition_preview', ['name' => old('name', '…'), 'code' => Str::lower(old('code', ''))]) }}
                                @else
                                    {{ __('settings.txn_statuses.permission_auto_hint') }}
                                @endif
                            @endunless
                        </p>
                        <small class="form-hint" data-status-permission-hint-required>{{ __('settings.txn_statuses.permission_roles_hint') }}</small>
                        <small class="form-hint" data-status-permission-hint-initial hidden>{{ __('settings.txn_statuses.initial_depends_create') }}</small>
                    </div>
                    <div class="form-group" data-visibility-scope-field @if (old('is_initial')) hidden @endif>
                        <x-input-label for="visibility_scope" :value="__('settings.txn_statuses.visibility_scope')" />
                        <select id="visibility_scope" name="visibility_scope" class="form-select">
                            <option value="unit" @selected(old('visibility_scope', 'unit') === 'unit')>{{ __('settings.txn_statuses.visibility_scope_unit') }}</option>
                            <option value="global" @selected(old('visibility_scope') === 'global')>{{ __('settings.txn_statuses.visibility_scope_global') }}</option>
                        </select>
                        <small class="form-hint">{{ __('settings.txn_statuses.visibility_scope_hint') }}</small>
                    </div>
                    <div class="form-grid form-grid--checks">
                        <div class="form-check">
                            <input id="is_initial" name="is_initial" type="checkbox" value="1" @checked(old('is_initial')) data-status-initial-toggle>
                            <x-input-label for="is_initial" :value="__('settings.txn_statuses.is_initial')" />
                        </div>
                        <div class="form-check">
                            <input id="is_final" name="is_final" type="checkbox" value="1" @checked(old('is_final'))>
                            <x-input-label for="is_final" :value="__('settings.txn_statuses.is_final')" />
                        </div>
                        <div class="form-check">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                            <x-input-label for="is_active" :value="__('common.active')" />
                        </div>
                    </div>
                    <x-primary-button>{{ __('common.add') }}</x-primary-button>
                </form>
            </div>
        </div>
        @endpermission

        <div class="ref-types-grid">
            @forelse ($statuses as $status)
                <div class="ref-type-card {{ $status->is_active ? '' : 'ref-type-card--inactive' }}">
                    <div class="ref-type-card-icon ref-type-card-icon--status" style="background: {{ $status->color ?? '#64748b' }}20; color: {{ $status->color ?? '#64748b' }}">
                        <span class="txn-status-order">{{ $status->sort_order }}</span>
                    </div>
                    <div class="ref-type-card-body">
                        <div class="ref-type-card-top">
                            <h3 class="ref-type-card-title">{{ $status->name }}</h3>
                            <span class="org-tree-code">{{ $status->code }}</span>
                        </div>
                        @if ($status->description)
                            <p class="ref-type-card-desc">{{ $status->description }}</p>
                        @endif
                        <div class="ref-type-card-meta">
                            @if ($status->is_initial)
                                <span class="settings-badge">{{ __('settings.txn_statuses.badge_initial') }}</span>
                            @endif
                            @if ($status->is_final)
                                <span class="settings-badge settings-badge--success">{{ __('settings.txn_statuses.badge_final') }}</span>
                            @endif
                            @if ($status->is_initial)
                                <span class="settings-badge settings-badge--muted">{{ __('settings.txn_statuses.badge_create_txn') }}</span>
                            @elseif ($status->required_permission)
                                <span class="settings-badge settings-badge--muted" title="{{ $status->required_permission }}">{{ $status->permissionLabel() }}</span>
                                <span class="settings-badge settings-badge--muted">{{ $status->isGlobalScope() ? __('settings.txn_statuses.badge_scope_global') : __('settings.txn_statuses.badge_scope_unit') }}</span>
                            @else
                                <span class="settings-badge settings-badge--danger">{{ __('settings.txn_statuses.badge_no_permission') }}</span>
                            @endif
                            @unless ($status->is_active)
                                <span class="settings-badge settings-badge--danger">{{ __('common.inactive') }}</span>
                            @endunless
                        </div>
                    </div>
                    @permission('settings.transaction-statuses.edit')
                    <details class="ref-type-edit">
                        <summary class="ref-type-edit-toggle">{{ __('common.edit') }}</summary>
                        <form method="POST" action="{{ route('settings.transaction-statuses.update', $status) }}" class="ref-type-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <x-input-label :value="__('common.name')" />
                                <x-text-input name="name" type="text" :value="old('name', $status->name)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('settings.txn_statuses.code')" />
                                <x-text-input name="code" type="text" :value="old('code', $status->code)" required />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('settings.txn_statuses.sort_order')" />
                                <x-text-input name="sort_order" type="number" :value="old('sort_order', $status->sort_order)" min="0" />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('settings.txn_statuses.color')" />
                                <x-text-input name="color" type="text" :value="old('color', $status->color)" />
                            </div>
                            <div class="form-group">
                                <x-input-label :value="__('common.description')" />
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $status->description) }}</textarea>
                            </div>
                            <div class="form-group" data-status-permission-field @if ($status->is_initial) hidden @endif>
                                <x-input-label :value="__('settings.txn_statuses.transition_permission')" />
                                @unless ($status->is_initial)
                                    <p class="form-hint">{{ $status->workflowPermissionLabel() }}</p>
                                    <span class="permission-checkbox-key">{{ $status->workflowPermissionKey() }}</span>
                                @endunless
                                <small class="form-hint" data-status-permission-hint-required @if ($status->is_initial) hidden @endif>{{ __('settings.txn_statuses.managed_in_roles') }}</small>
                                <small class="form-hint" data-status-permission-hint-initial @unless ($status->is_initial) hidden @endunless>{{ __('settings.txn_statuses.initial_depends_create') }}</small>
                            </div>
                            <div class="form-group" data-visibility-scope-field @if ($status->is_initial) hidden @endif>
                                <x-input-label :value="__('settings.txn_statuses.visibility_scope')" />
                                <select name="visibility_scope" class="form-select">
                                    <option value="unit" @selected(old('visibility_scope', $status->visibility_scope ?? 'unit') === 'unit')>{{ __('settings.txn_statuses.visibility_scope_unit') }}</option>
                                    <option value="global" @selected(old('visibility_scope', $status->visibility_scope ?? 'unit') === 'global')>{{ __('settings.txn_statuses.visibility_scope_global') }}</option>
                                </select>
                                <small class="form-hint">{{ __('settings.txn_statuses.visibility_scope_hint') }}</small>
                            </div>
                            <div class="form-check form-group">
                                <input name="is_initial" type="checkbox" value="1" @checked(old('is_initial', $status->is_initial)) data-status-initial-toggle>
                                <x-input-label :value="__('settings.txn_statuses.is_initial')" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_final" type="checkbox" value="1" @checked(old('is_final', $status->is_final))>
                                <x-input-label :value="__('settings.txn_statuses.is_final')" />
                            </div>
                            <div class="form-check form-group">
                                <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $status->is_active))>
                                <x-input-label :value="__('common.active')" />
                            </div>
                            <div class="form-actions">
                                <x-primary-button>{{ __('common.save') }}</x-primary-button>
                            </div>
                        </form>
                        @permission('settings.transaction-statuses.delete')
                        <form method="POST" action="{{ route('settings.transaction-statuses.destroy', $status) }}" class="ref-type-delete-form" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('common.delete') }}</button>
                        </form>
                        @endpermission
                    </details>
                    @endpermission
                </div>
            @empty
                <div class="settings-empty" style="grid-column:1/-1;">
                    <p>{{ __('settings.txn_statuses.empty') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    @push('scripts')
        <script>
            const txnStatusI18n = @json($txnStatusI18n);

            document.querySelectorAll('[data-status-initial-toggle]').forEach((checkbox) => {
                const form = checkbox.closest('form');
                const permissionField = form?.querySelector('[data-status-permission-field]');
                const visibilityScopeField = form?.querySelector('[data-visibility-scope-field]');
                const hintRequired = form?.querySelector('[data-status-permission-hint-required]');
                const hintInitial = form?.querySelector('[data-status-permission-hint-initial]');
                const nameInput = form?.querySelector('[name="name"]');
                const codeInput = form?.querySelector('[name="code"]');
                const preview = form?.querySelector('[data-workflow-permission-preview]');

                const sync = () => {
                    const isInitial = checkbox.checked;

                    if (hintRequired) {
                        hintRequired.hidden = isInitial;
                    }

                    if (hintInitial) {
                        hintInitial.hidden = ! isInitial;
                    }

                    if (permissionField) {
                        permissionField.hidden = isInitial;
                    }

                    if (visibilityScopeField) {
                        visibilityScopeField.hidden = isInitial;
                    }

                    if (preview && nameInput && codeInput) {
                        const name = nameInput.value.trim();
                        const code = codeInput.value.trim().toLowerCase();

                        if (isInitial) {
                            preview.textContent = '';
                        } else if (name || code) {
                            preview.innerHTML = `${txnStatusI18n.transitionPrefix} — ${name || '…'} <span class="permission-checkbox-key">transactions.workflow.${code || '…'}</span>`;
                        } else {
                            preview.textContent = txnStatusI18n.autoPermissionHint;
                        }
                    }
                };

                checkbox.addEventListener('change', sync);
                nameInput?.addEventListener('input', sync);
                codeInput?.addEventListener('input', sync);
                sync();
            });
        </script>
    @endpush
</x-app-layout>
