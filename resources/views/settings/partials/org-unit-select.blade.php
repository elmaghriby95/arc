@props([
    'name' => 'department_id',
    'id' => 'department_id',
    'orgUnits' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => '— اختر موقعك في الهيكل التنظيمي —',
    'showHint' => true,
    'selectClass' => 'form-select org-unit-select',
])

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
        <p class="org-unit-picker-hint">حدّد الوحدة التي يتبعها المستخدم — يحدد ذلك ما يظهر له في المنظومة (وحدته + الوحدات التابعة لها).</p>
    @endif
</div>
