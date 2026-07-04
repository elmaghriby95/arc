@props([
    'parentId' => null,
    'defaultUnitLabel' => 'قطاع',
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
            <x-input-label value="المسمى (نوع الوحدة)" />
            <input
                name="unit_label"
                type="text"
                class="form-control"
                list="org-unit-labels"
                value="{{ old('unit_label', $defaultUnitLabel) }}"
                placeholder="مثال: قطاع، إدارة، قسم"
                required
            >
        </div>
        <div class="form-group">
            <x-input-label value="الاسم" />
            <x-text-input name="name" type="text" :value="old('name')" placeholder="اسم الوحدة التنظيمية" required />
        </div>
        <div class="form-group">
            <x-input-label value="المدير / الرئيس" />
            <select name="head_id" class="form-select">
                <option value="">— بدون تعيين —</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('head_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-group">
        <x-input-label value="الوصف (اختياري)" />
        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
    </div>
    <div class="form-check form-group">
        <input name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
        <x-input-label value="نشط" />
    </div>
    <div class="form-actions">
        <x-primary-button>{{ $parentId ? 'إضافة' : 'إضافة وحدة رئيسية' }}</x-primary-button>
    </div>
</form>
