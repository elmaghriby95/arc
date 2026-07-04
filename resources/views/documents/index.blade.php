<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">الوثائق</h1>
                <p class="page-subtitle">إدارة وبحث جميع الوثائق المؤرشفة</p>
            </div>
            @permission('documents.create')
                <a href="{{ route('documents.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    إضافة وثيقة
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">تصفية البحث</h3>
                <p class="card-subtitle">ابحث وفلتر الوثائق حسب القسم والتصنيف والحالة</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" value="بحث" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" placeholder="العنوان أو الرقم المرجعي" />
                </div>
                <div class="form-group">
                    <x-input-label for="department_id" value="الوحدة التنظيمية" />
                    @include('settings.partials.org-unit-select', [
                        'orgUnits' => $orgUnits,
                        'selected' => request('department_id'),
                        'placeholder' => '— الكل —',
                        'showHint' => false,
                    ])
                </div>
                <div class="form-group">
                    <x-input-label for="category_id" value="التصنيف" />
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="status" value="الحالة" />
                    <select id="status" name="status" class="form-select">
                        <option value="">الكل</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') == $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <x-primary-button>تصفية</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">قائمة الوثائق</h3>
                <p class="card-subtitle">{{ $documents->total() }} وثيقة</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>الرقم المرجعي</th>
                            <th>العنوان</th>
                            <th>القسم</th>
                            <th>التصنيف</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td><a href="{{ route('documents.show', $document) }}" class="ref-pill">{{ $document->reference_number }}</a></td>
                                <td class="table-title">{{ $document->title }}</td>
                                <td>{{ $document->department?->name ?? '—' }}</td>
                                <td>{{ $document->category?->name ?? '—' }}</td>
                                <td><x-status-badge :status="$document->status" /></td>
                                <td class="table-actions">
                                    <a href="{{ route('documents.show', $document) }}">عرض</a>
                                    @permission('documents.edit')
                                        <a href="{{ route('documents.edit', $document) }}">تعديل</a>
                                    @endpermission
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state" style="padding:2rem;">
                                        <p class="text-muted">لا توجد وثائق مطابقة.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 1rem 1.5rem;">{{ $documents->links() }}</div>
        </div>
    </div>
</x-app-layout>
