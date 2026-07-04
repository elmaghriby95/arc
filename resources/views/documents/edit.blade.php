<x-app-layout>
    <x-slot name="header">
        <h2 class="page-title">تعديل وثيقة</h2>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('documents.update', $document) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <x-input-label for="title" value="عنوان الوثيقة" />
                        <x-text-input id="title" name="title" type="text" :value="old('title', $document->title)" required />
                    </div>

                    <div class="form-group">
                        <x-input-label for="description" value="الوصف" />
                        <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $document->description) }}</textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="department_id" value="الوحدة التنظيمية" />
                            @include('settings.partials.org-unit-select', [
                                'orgUnits' => $orgUnits,
                                'selected' => old('department_id', $document->department_id),
                            ])
                        </div>
                        <div class="form-group">
                            <x-input-label for="category_id" value="التصنيف" />
                            <select id="category_id" name="category_id" class="form-select">
                                <option value="">—</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $document->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <x-input-label for="document_date" value="تاريخ الوثيقة" />
                            <x-text-input id="document_date" name="document_date" type="date" :value="old('document_date', $document->document_date?->format('Y-m-d'))" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="status" value="الحالة" />
                            <select id="status" name="status" class="form-select" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $document->status->value) == $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <x-input-label for="tags" value="الكلمات المفتاحية (مفصولة بفاصلة)" />
                        <x-text-input id="tags" name="tags" type="text" :value="old('tags', $document->tags->pluck('name')->join(', '))" />
                    </div>

                    <div class="form-check form-group">
                        <input id="is_confidential" name="is_confidential" type="checkbox" value="1" @checked(old('is_confidential', $document->is_confidential))>
                        <x-input-label for="is_confidential" value="وثيقة سرية" />
                    </div>

                    <div class="form-group">
                        <x-input-label for="file" value="استبدال الملف (اختياري)" />
                        <input id="file" name="file" type="file" class="form-control">
                    </div>

                    <div class="form-group">
                        <x-input-label for="change_note" value="ملاحظة التغيير" />
                        <x-text-input id="change_note" name="change_note" type="text" :value="old('change_note')" />
                    </div>

                    <div class="form-actions">
                        <x-primary-button>تحديث</x-primary-button>
                        <a href="{{ route('documents.show', $document) }}" class="btn btn-link">إلغاء</a>
                    </div>
                </form>

                @permission('documents.delete')
                <form method="POST" action="{{ route('documents.destroy', $document) }}" style="margin-top:1.5rem; padding-top:1.5rem; border-top:1px solid var(--border);" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوثيقة؟');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>حذف الوثيقة</x-danger-button>
                </form>
                @endpermission
            </div>
        </div>
    </div>
</x-app-layout>
