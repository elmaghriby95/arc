@props([
    'name' => 'department_id',
    'id' => 'department_id',
    'orgUnits' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'showHint' => true,
    'selectClass' => 'form-select org-unit-select',
])

@php
    $placeholder = $placeholder ?? __('common.choose_org_unit');
@endphp

<div class="org-unit-picker">
    <select id="{{ $id }}" name="{{ $name }}" class="{{ $selectClass }}" @if($required) required @endif>
        <option value="">{{ $placeholder }}</option>
        @foreach ($orgUnits as $unit)
            <option
                value="{{ $unit['id'] }}"
                @selected(old($name, $selected) == $unit['id'])
                data-depth="{{ $unit['depth'] }}"
            >{{ str_repeat(' ', $unit['depth']) }}{{ $unit['depth'] > 0 ? '↳ ' : '' }}{{ $unit['label'] }}</option>
        @endforeach
    </select>
    @if ($showHint)
        <p class="org-unit-picker-hint">{{ __('settings.org.unit_hint') }}</p>
    @endif
</div>
