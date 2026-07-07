<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('settings.index') }}" class="settings-back-link">{{ __('common.back') }}</a>
                <h2 class="page-title">{{ __('settings.folders.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.folders.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="org-stats">
            <div class="org-stat">
                <span class="org-stat-value">{{ $totalFolders }}</span>
                <span class="org-stat-label">{{ __('settings.folders.total') }}</span>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.folders.add_title') }}</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.folders.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="name" :value="__('settings.folders.name')" />
                            <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                        </div>
                        <div class="form-group">
                            <x-input-label for="parent_id" :value="__('settings.folders.parent')" />
                            <select id="parent_id" name="parent_id" class="form-select">
                                <option value="">{{ __('settings.folders.root_folder') }}</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <x-input-label for="department_id" :value="__('common.org_unit')" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id'),
                                'placeholder' => __('common.choose_org_unit'),
                                'showHint' => false,
                                'required' => true,
                            ])
                            <p class="form-hint">{{ __('settings.folders.org_unit_hint') }}</p>
                            @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @permission('settings.folders.location.edit')
                            @include('settings.partials.folder-location-fields', ['idPrefix' => ''])
                        @endpermission
                        <div class="form-group">
                            <x-input-label for="color" :value="__('settings.folders.color')" />
                            <x-text-input id="color" name="color" type="text" :value="old('color')" placeholder="#4338ca" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="sort_order" :value="__('settings.folders.sort_order')" />
                            <x-text-input id="sort_order" name="sort_order" type="number" :value="old('sort_order', 0)" min="0" />
                        </div>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" :value="__('common.description')" />
                        <textarea id="description" name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <x-input-label for="is_active" :value="__('common.active')" />
                    </div>
                    <x-primary-button>{{ __('settings.folders.add_button') }}</x-primary-button>
                </form>
            </div>
        </div>

        <div class="card org-tree-card-wrapper">
            <div class="card-header">
                <h3 class="card-title">{{ __('settings.folders.tree_title') }}</h3>
                <button type="button" class="btn btn-secondary" data-org-expand-all>{{ __('common.expand_all') }}</button>
            </div>
            <div class="card-body">
                @if ($folders->isEmpty())
                    <div class="settings-empty">
                        <div class="settings-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                        </div>
                        <p>{{ __('settings.folders.empty') }}</p>
                    </div>
                @else
                    <ul class="org-tree" data-org-tree>
                        @foreach ($folders as $folder)
                            @include('settings.partials.folder-tree-node', ['folder' => $folder, 'depth' => 0, 'breadcrumbs' => $breadcrumbs, 'orgUnits' => $orgUnits])
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
