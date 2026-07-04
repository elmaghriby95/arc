<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">إضافة وثيقة</h2>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <x-input-label for="title" value="عنوان الوثيقة" />
                        <x-text-input id="title" name="title" type="text" :value="old('title')" required />
                    </div>

                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="department_id" value="الوحدة التنظيمية" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id', $defaultDepartmentId ?? null),
                            ])
                        </div>
                        <div class="form-group">
                            <x-input-label for="category_id" value="التصنيف" />
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="document_date" value="تاريخ الوثيقة" />
                            <x-text-input id="document_date" name="document_date" type="date" :value="old('document_date')" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="status" value="الحالة" />
                            <select id="status" name="status" class="form-select" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', 'active') == $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <x-input-label for="tags" value="الكلمات المفتاحية (مفصولة بفاصلة)" />
                        <x-text-input id="tags" name="tags" type="text" :value="old('tags')" />
                    </div>

                    <div class="form-check form-group">
                        <input id="is_confidential" name="is_confidential" type="checkbox" value="1" @checked(old('is_confidential'))>
                        <x-input-label for="is_confidential" value="وثيقة سرية" />
                    </div>

                    <div class="form-group">
                        <x-input-label for="file" value="الملف" />
                        <input id="file" name="file" type="file" class="form-control" required>
                    </div>

                    <div class="form-actions">
                        <x-primary-button>حفظ</x-primary-button>
                        <a href="{{ route('documents.index') }}" class="btn btn-link">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
