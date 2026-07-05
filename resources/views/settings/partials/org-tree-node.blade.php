@props(['department', 'depth' => 0, 'users' => collect(), 'unitLabelSuggestions' => []])

@php
    $childDefaults = [
        __('settings.org.unit_sector'),
        __('settings.org.unit_administration'),
        __('settings.org.unit_department'),
        __('settings.org.unit_unit'),
        __('settings.org.unit_office'),
        __('settings.org.unit_branch'),
    ];
    $defaultChildLabel = $childDefaults[min($depth + 1, count($childDefaults) - 1)] ?? __('settings.org.unit_unit');
    $hasChildren = $department->children->isNotEmpty();
@endphp

<li class="org-tree-node" data-org-node>
    <div class="org-tree-item" style="--depth: {{ $depth }}">
        @if ($hasChildren)
            <button type="button" class="org-tree-toggle" data-org-toggle aria-expanded="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        @else
            <span class="org-tree-spacer"></span>
        @endif

        <div class="org-tree-card {{ $department->is_active ? '' : 'org-tree-card--inactive' }}">
            <div class="org-tree-card-icon">
                @if ($depth === 0)
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                @endif
            </div>
            <div class="org-tree-card-body">
                <div class="org-tree-card-top">
                    @if ($department->unit_label)
                        <span class="org-tree-unit-label">{{ $department->unit_label }}</span>
                    @endif
                    <strong class="org-tree-card-name">{{ $department->name }}</strong>
                    <span class="org-tree-code">{{ $department->code }}</span>
                </div>
                @if ($department->description)
                    <p class="org-tree-card-desc">{{ $department->description }}</p>
                @endif
                <div class="org-tree-card-meta">
                    @if ($department->head)
                        <span class="settings-badge settings-badge--primary">
                            {{ $department->unit_label ? __('settings.org.head_of', ['unit' => $department->unit_label]) : __('settings.org.manager') }}: {{ $department->head->name }}
                        </span>
                    @endif
                    <span class="settings-badge">{{ __('settings.org.employees_count', ['count' => $department->users_count]) }}</span>
                    @if ($hasChildren)
                        <span class="settings-badge settings-badge--muted">{{ __('settings.org.sub_units_count', ['count' => $department->children->count()]) }}</span>
                    @endif
                    @unless ($department->is_active)
                        <span class="settings-badge settings-badge--danger">{{ __('common.inactive') }}</span>
                    @endunless
                </div>
            </div>

            @permission('departments.edit')
                <details class="org-tree-edit-panel">
                    <summary class="org-tree-edit" title="{{ __('common.edit') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </summary>
                    <form method="POST" action="{{ route('settings.organization.update', $department) }}" class="org-inline-form org-edit-form">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <x-input-label :value="__('settings.org.unit_label')" />
                            <input
                                name="unit_label"
                                type="text"
                                class="form-control"
                                list="org-unit-labels"
                                value="{{ old('unit_label', $department->unit_label) }}"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <x-input-label :value="__('common.name')" />
                            <x-text-input name="name" type="text" :value="old('name', $department->name)" required />
                        </div>
                        <div class="form-group">
                            <x-input-label :value="__('settings.org.head')" />
                            <select name="head_id" class="form-select">
                                <option value="">{{ __('settings.org.no_head') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('head_id', $department->head_id) == $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <x-input-label :value="__('common.description')" />
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $department->description) }}</textarea>
                        </div>
                        <div class="form-check form-group">
                            <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $department->is_active))>
                            <x-input-label :value="__('common.active')" />
                        </div>
                        <div class="form-actions">
                            <x-primary-button>{{ __('common.save') }}</x-primary-button>
                            @permission('departments.delete')
                                <button type="submit" formaction="{{ route('settings.organization.destroy', $department) }}" formmethod="POST" class="btn btn-danger" onclick="this.form.querySelector('[name=_method]').value='DELETE'; return confirm(@json(__('settings.org.confirm_delete')))">
                                    {{ __('common.delete') }}
                                </button>
                            @endpermission
                        </div>
                    </form>
                </details>
            @endpermission
        </div>
    </div>

    <ul class="org-tree-children{{ $hasChildren ? '' : ' org-tree-children--empty' }}" data-org-children>
        @foreach ($department->children as $child)
            @include('settings.partials.org-tree-node', [
                'department' => $child,
                'depth' => $depth + 1,
                'users' => $users,
                'unitLabelSuggestions' => $unitLabelSuggestions,
            ])
        @endforeach

        @permission('departments.create')
            <li class="org-tree-add-node">
                <details class="org-tree-add">
                    <summary class="org-tree-add-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('settings.org.add_sub_unit') }}
                    </summary>
                    @include('settings.partials.org-add-form', [
                        'parentId' => $department->id,
                        'defaultUnitLabel' => $defaultChildLabel,
                        'users' => $users,
                        'unitLabelSuggestions' => $unitLabelSuggestions,
                    ])
                </details>
            </li>
        @endpermission
    </ul>
</li>
