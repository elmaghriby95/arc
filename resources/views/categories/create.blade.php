<x-app-layout>
    <x-slot name="header"><h2 class="page-title">إضافة تصنيف</h2></x-slot>

    <div class="container">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="form-group">
                        <x-input-label for="name" value="اسم التصنيف" />
                        <x-text-input id="name" name="name" type="text" :value="old('name')" required />
                    </div>
                    <div class="form-group">
                        <x-input-label for="parent_id" value="التصنيف الأب" />
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="">—</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <x-input-label for="sort_order" value="الترتيب" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="0" :value="old('sort_order', 0)" />
                    </div>
                    <div class="form-check form-group">
                        <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                        <x-input-label for="is_active" value="نشط" />
                    </div>
                    <x-primary-button>حفظ</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
