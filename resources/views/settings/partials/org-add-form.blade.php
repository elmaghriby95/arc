@props([
    'parentId' => null,
    'defaultUnitLabel' => __('settings.org.sector'),
    'users' => collect(),
    'unitLabelSuggestions' => [],
])

<form method="POST" action="{{ route('settings.organization.store') }}" class="org-inline-form{{ $parentId ? '' : ' org-inline-form--root' }}">
    @csrf
    @if ($parentId)
        <input type="hidden" name="parent_id" value="{{ $parentId }}">
    @endif
    <div class="form-grid">
        <div class="form-group">
            <x-input-label :value="__('settings.org.unit_label')" />
            <input
                name="unit_label"
                type="text"
                class="form-control"
                list="org-unit-labels"
                value="{{ old('unit_label', $defaultUnitLabel) }}"
                placeholder="{{ __('settings.org.unit_label_placeholder') }}"
                required
            >
        </div>
        <div class="form-group">
            <x-input-label :value="__('common.name')" />
            <x-text-input name="name" type="text" :value="old('name')" :placeholder="__('settings.org.name_placeholder')" required />
        </div>
        <div class="form-group">
            <x-input-label :value="__('settings.org.head')" />
            <select name="head_id" class="form-select">
                <option value="">{{ __('settings.org.no_head') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('head_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group">
        <x-input-label :value="__('settings.org.description_optional')" />
        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
    </div>
    <div class="form-check form-group">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
        <x-input-label :value="__('common.active')" />
    </div>
    <div class="form-actions">
        <x-primary-button>{{ $parentId ? __('common.add') : __('settings.org.add_root') }}</x-primary-button>
    </div>
</form>
