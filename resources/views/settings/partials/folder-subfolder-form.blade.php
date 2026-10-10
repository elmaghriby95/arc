@props([
    'parentFolder',
    'orgUnits' => [],
    'idPrefix' => '',
    'restoreOld' => false,
])

<form method="POST" action="{{ route('settings.folders.store') }}" class="org-inline-form folder-subfolder-form">
    @csrf
    <input type="hidden" name="add_context" value="tree-{{ $parentFolder->id }}">
    <input type="hidden" name="parent_id" value="{{ $parentFolder->id }}">

    <div class="form-group">
        <x-input-label :for="$idPrefix.'parent_label'" :value="__('settings.folders.parent')" />
        <x-text-input
            :id="$idPrefix.'parent_label'"
            type="text"
            :value="$parentFolder->name"
            readonly
        />
        <p class="form-hint">{{ __('settings.folders.parent_auto') }}</p>
    </div>

    <div class="form-group">
        <x-input-label :for="$idPrefix.'name'" :value="__('settings.folders.name')" />
        <x-text-input :id="$idPrefix.'name'" name="name" type="text" :value="$restoreOld ? old('name') : ''" required />
        @if ($restoreOld)
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        @endif
    </div>

    <div class="form-group">
        <x-input-label :for="$idPrefix.'department_id'" :value="__('common.org_unit')" />
        @include('settings.partials.org-unit-select', [
            'id' => $idPrefix.'department_id',
            'orgUnits' => $orgUnits,
            'selected' => $restoreOld ? old('department_id', $parentFolder->department_id) : $parentFolder->department_id,
            'ignoreOld' => ! $restoreOld,
            'placeholder' => __('common.choose_org_unit'),
            'showHint' => false,
            'required' => true,
        ])
        @if ($restoreOld)
            @error('department_id')<p class="form-error">{{ $message }}</p>@enderror
        @endif
    </div>

    @permission('settings.folders.location.edit')
        @include('settings.partials.folder-location-fields', [
            'idPrefix' => $idPrefix,
            'useOld' => $restoreOld,
        ])
    @endpermission

    <div class="form-grid">
        <div class="form-group">
            <x-input-label :for="$idPrefix.'color'" :value="__('settings.folders.color')" />
            <x-text-input :id="$idPrefix.'color'" name="color" type="text" :value="$restoreOld ? old('color') : ''" placeholder="#4338ca" />
        </div>
        <div class="form-group">
            <x-input-label :for="$idPrefix.'sort_order'" :value="__('settings.folders.sort_order')" />
            <x-text-input :id="$idPrefix.'sort_order'" name="sort_order" type="number" :value="$restoreOld ? old('sort_order', 0) : 0" min="0" />
        </div>
    </div>

    <div class="form-group">
        <x-input-label :for="$idPrefix.'description'" :value="__('common.description')" />
        <textarea id="{{ $idPrefix }}description" name="description" rows="2" class="form-control">{{ $restoreOld ? old('description') : '' }}</textarea>
    </div>

    <div class="form-check form-group">
        <input id="{{ $idPrefix }}is_active" name="is_active" type="checkbox" value="1" @checked(! $restoreOld || old('is_active', true))>
        <x-input-label :for="$idPrefix.'is_active'" :value="__('common.active')" />
    </div>

    <div class="form-actions">
        <x-primary-button>{{ __('settings.folders.add_sub_folder') }}</x-primary-button>
    </div>
</form>
