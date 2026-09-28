@props([
    'cabinetNumber' => null,
    'rowNumber' => null,
    'boxNumber' => null,
    'idPrefix' => '',
    'useOld' => true,
])

@php
    $cabinetValue = $useOld ? old('cabinet_number', $cabinetNumber) : $cabinetNumber;
    $rowValue = $useOld ? old('row_number', $rowNumber) : $rowNumber;
    $boxValue = $useOld ? old('box_number', $boxNumber) : $boxNumber;
@endphp

<div class="form-group">
    <x-input-label :for="$idPrefix.'cabinet_number'" :value="__('settings.folders.cabinet_number')" />
    <x-text-input :id="$idPrefix.'cabinet_number'" name="cabinet_number" type="text" :value="$cabinetValue" required />
    @if ($useOld)
        @error('cabinet_number')<p class="form-error">{{ $message }}</p>@enderror
    @endif
</div>
<div class="form-group">
    <x-input-label :for="$idPrefix.'row_number'" :value="__('settings.folders.row_number')" />
    <x-text-input :id="$idPrefix.'row_number'" name="row_number" type="text" :value="$rowValue" required />
    @if ($useOld)
        @error('row_number')<p class="form-error">{{ $message }}</p>@enderror
    @endif
</div>
<div class="form-group">
    <x-input-label :for="$idPrefix.'box_number'" :value="__('settings.folders.box_number')" />
    <x-text-input :id="$idPrefix.'box_number'" name="box_number" type="text" :value="$boxValue" required />
    @if ($useOld)
        @error('box_number')<p class="form-error">{{ $message }}</p>@enderror
    @endif
</div>
