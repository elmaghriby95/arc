@props([
    'name' => 'department_id',
    'id' => 'department_id',
    'orgUnits' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => null,
    'showHint' => true,
    'selectClass' => 'form-select org-unit-select',
    'ignoreOld' => false,
    'lockWhenSingle' => false,
])

@php
    $placeholder = $placeholder ?? __('common.choose_org_unit');
    $singleUnit = ($lockWhenSingle && count($orgUnits) === 1) ? reset($orgUnits) : null;
    $locked = $singleUnit !== null;
    $current = $locked
        ? $singleUnit['id']
        : ($ignoreOld ? $selected : old($name, $selected));
@endphp

<div class="org-unit-picker {{ $locked ? 'is-locked' : '' }}">
    @if ($locked)
        <input type="hidden" name="{{ $name }}" value="{{ $current }}">
    @endif
    <select
        id="{{ $id }}"
        @unless($locked) name="{{ $name }}" @endunless
        class="{{ $selectClass }}"
        @if($required && ! $locked) required @endif
        @if($locked) disabled data-locked="1" aria-disabled="true" title="{{ __('transactions.org_unit_locked') }}" @endif
    >
        @unless($locked)
            <option value="">{{ $placeholder }}</option>
        @endunless
        @foreach ($orgUnits as $unit)
            <option
                value="{{ $unit['id'] }}"
                @selected($current == $unit['id'])
                data-depth="{{ $unit['depth'] }}"
            >{{ str_repeat(' ', $unit['depth']) }}{{ $unit['depth'] > 0 ? '↳ ' : '' }}{{ $unit['label'] }}</option>
        @endforeach
    </select>
    @if ($locked)
        <p class="org-unit-locked-hint">{{ __('transactions.org_unit_locked') }}</p>
    @elseif ($showHint)
        <p class="org-unit-picker-hint">{{ __('settings.org.unit_hint') }}</p>
    @endif
</div>
