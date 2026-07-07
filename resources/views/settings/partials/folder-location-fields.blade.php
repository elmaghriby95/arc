@props([
    'cabinetNumber' => null,
    'rowNumber' => null,
    'boxNumber' => null,
    'idPrefix' => '',
])

<div class="form-group">
    <x-input-label :for="$idPrefix.'cabinet_number'" :value="__('settings.folders.cabinet_number')" />
    <x-text-input :id="$idPrefix.'cabinet_number'" name="cabinet_number" type="text" :value="old('cabinet_number', $cabinetNumber)" required />
    @error('cabinet_number')<p class="form-error">{{ $message }}</p>@enderror
</div>
<div class="form-group">
    <x-input-label :for="$idPrefix.'row_number'" :value="__('settings.folders.row_number')" />
    <x-text-input :id="$idPrefix.'row_number'" name="row_number" type="text" :value="old('row_number', $rowNumber)" required />
    @error('row_number')<p class="form-error">{{ $message }}</p>@enderror
</div>
<div class="form-group">
    <x-input-label :for="$idPrefix.'box_number'" :value="__('settings.folders.box_number')" />
    <x-text-input :id="$idPrefix.'box_number'" name="box_number" type="text" :value="old('box_number', $boxNumber)" required />
    @error('box_number')<p class="form-error">{{ $message }}</p>@enderror
</div>
