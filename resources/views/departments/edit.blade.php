<x-app-layout>
    <x-slot name="header"><h2 class="page-title">تعديل قسم</h2></x-slot>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('departments.update', $department) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <x-input-label for="name" value="اسم القسم" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $department->name)" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="code" value="رمز القسم" />
                        <x-text-input id="code" name="code" type="text" :value="old('code', $department->code)" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="parent_id" value="القسم الأب (اختياري)" />
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="">— بدون قسم أب —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $department->parent_id) == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $department->description) }}</textarea>
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $department->is_active))>
                        <x-input-label for="is_active" value="نشط" />
                    </div>
                    <x-primary-button>تحديث</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
