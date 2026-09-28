@props(['folder', 'depth' => 0])

@php
    $hasChildren = $folder->children->isNotEmpty();
    $canCreateFolder = auth()->user()?->hasPermission('settings.folders.create') ?? false;
    $subfolderContext = 'tree-'.$folder->id;
    $restoreSubfolder = old('add_context') === $subfolderContext;
@endphp

<li class="org-tree-node folder-tree-node {{ $hasChildren ? 'is-collapsed' : '' }}" data-org-node>
    <div class="org-tree-item">
        @if ($hasChildren)
            <button type="button" class="org-tree-toggle" data-org-toggle aria-expanded="false" aria-label="{{ __('transactions.show_subfolders') }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </button>
        @else
            <span class="org-tree-spacer"></span>
        @endif

        <div class="org-tree-card folder-tree-card {{ $folder->is_active ? '' : 'org-tree-card--inactive' }}" @if($folder->color) style="border-right-color: {{ $folder->color }};" @endif>
            <div class="org-tree-card-icon folder-tree-icon" @if($folder->color) style="background: {{ $folder->color }}20; color: {{ $folder->color }};" @endif>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                </svg>
            </div>
            <div class="org-tree-card-body">
                <div class="folder-tree-headline">
                    <strong class="folder-tree-name" title="{{ $folder->name }}">{{ $folder->name }}</strong>
                    @if ($hasChildren)
                        <span class="folder-tree-count" title="{{ __('settings.folders.sub_folders_count', ['count' => $folder->children->count()]) }}">{{ $folder->children->count() }}</span>
                    @endif
                    @unless ($folder->is_active)
                        <span class="settings-badge settings-badge--danger">{{ __('common.inactive') }}</span>
                    @endunless
                    @permission('settings.folders.location.view')
                        @if ($folder->locationLabel())
                            <span class="settings-badge settings-badge--primary">{{ $folder->locationLabel() }}</span>
                        @endif
                    @endpermission
                </div>
                @if ($folder->department_id && isset($breadcrumbs[$folder->department_id]))
                    <p class="folder-tree-unit" title="{{ $breadcrumbs[$folder->department_id] }}">{{ $breadcrumbs[$folder->department_id] }}</p>
                @endif
            </div>
            <div class="folder-tree-actions">
                @if ($canCreateFolder)
                    <details class="folder-tree-add" @if($restoreSubfolder) open @endif>
                        <summary class="folder-tree-add-btn" title="{{ __('settings.folders.add_sub_folder') }}" aria-label="{{ __('settings.folders.add_sub_folder') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </summary>
                        @include('settings.partials.folder-subfolder-form', [
                            'parentFolder' => $folder,
                            'orgUnits' => $orgUnits,
                            'idPrefix' => 'sub_'.$folder->id.'_',
                            'restoreOld' => $restoreSubfolder,
                        ])
                    </details>
                @endif
            <details class="folder-tree-edit">
                <summary class="org-tree-edit" title="{{ __('common.edit') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                </summary>
                <div class="folder-edit-panel">
                <form method="POST" action="{{ route('settings.folders.update', $folder) }}" id="folder-update-form-{{ $folder->id }}" class="folder-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <x-input-label :value="__('common.name')" />
                        <x-text-input name="name" type="text" :value="$folder->name" required />
                    </div>
                    <div class="form-group">
                        <x-input-label :value="__('common.description')" />
                        <textarea name="description" rows="2" class="form-control">{{ $folder->description }}</textarea>
                    </div>
                    <div class="form-group">
                        <x-input-label :value="__('common.org_unit')" />
                        @include('settings.partials.org-unit-select', [
                            'name' => 'department_id',
                            'id' => 'department_id_'.$folder->id,
                            'orgUnits' => $orgUnits,
                            'selected' => $folder->department_id,
                            'placeholder' => __('common.choose_org_unit'),
                            'showHint' => false,
                            'required' => true,
                        ])
                    </div>
                    @permission('settings.folders.location.edit')
                        @include('settings.partials.folder-location-fields', [
                            'cabinetNumber' => $folder->cabinet_number,
                            'rowNumber' => $folder->row_number,
                            'boxNumber' => $folder->box_number,
                            'idPrefix' => 'folder_'.$folder->id.'_',
                        ])
                    @endpermission
                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label :value="__('settings.folders.color')" />
                            <x-text-input name="color" type="text" :value="$folder->color" placeholder="#4338ca" />
                        </div>
                        <div class="form-group">
                            <x-input-label :value="__('settings.folders.sort_order')" />
                            <x-text-input name="sort_order" type="number" :value="$folder->sort_order" min="0" />
                        </div>
                    </div>
                    <div class="form-check form-group">
                        <input name="is_active" type="checkbox" value="1" @checked($folder->is_active)>
                        <x-input-label :value="__('common.active')" />
                    </div>
                </form>
                <div class="form-actions">
                    <x-primary-button form="folder-update-form-{{ $folder->id }}">{{ __('common.save') }}</x-primary-button>
                    @if (($folder->transactions_count ?? 0) > 0)
                        <p class="form-hint">{{ __('messages.folder.cannot_delete_in_use') }}</p>
                    @else
                        <form method="POST" action="{{ route('settings.folders.destroy', $folder) }}" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">{{ __('common.delete') }}</button>
                        </form>
                    @endif
                </div>
                </div>
            </details>
            </div>
        </div>
    </div>

    @if ($hasChildren)
        <ul class="org-tree-children is-hidden" data-org-children>
            @foreach ($folder->children as $child)
                @include('settings.partials.folder-tree-node', ['folder' => $child, 'depth' => $depth + 1, 'breadcrumbs' => $breadcrumbs, 'orgUnits' => $orgUnits])
            @endforeach
        </ul>
    @endif
</li>
