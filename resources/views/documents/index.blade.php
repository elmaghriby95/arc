<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">الوثائق</h1>
                <p class="page-subtitle">المستندات المرفقة بالمعاملات في نطاقك التنظيمي</p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    إنشاء معاملة
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">تصفية البحث</h3>
                <p class="card-subtitle">ابحث في المستندات أو المعاملات المرتبطة بها</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <x-input-label for="search" value="بحث" />
                    <x-text-input id="search" name="search" type="text" :value="request('search')" placeholder="اسم المستند أو عنوان المعاملة أو الرقم الإشاري" />
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
                <p class="card-subtitle">{{ $attachments->total() }} مستند مرفق بمعاملات</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>المستند</th>
                            <th>المعاملة</th>
                            <th>القسم</th>
                            <th>نوع الملف</th>
                            <th>رفع بواسطة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($attachments as $attachment)
                            <tr>
                                <td class="table-title">{{ $attachment->displayName() }}</td>
                                <td>
                                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="ref-pill">{{ $attachment->transaction->reference_number }}</a>
                                </td>
                                <td>{{ $attachment->transaction->department?->name ?? '—' }}</td>
                                <td>{{ strtoupper($attachment->fileKind()) }}</td>
                                <td>{{ $attachment->uploader?->name ?? '—' }}</td>
                                <td class="text-muted">{{ $attachment->created_at->format('Y-m-d') }}</td>
                                <td class="table-actions">
                                    <a href="{{ route('documents.show', $attachment) }}">عرض</a>
                                    @permission('documents.download')
                                        <a href="{{ route('documents.download', $attachment) }}">تحميل</a>
                                    @endpermission
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state" style="padding:2rem;">
                                        <p class="text-muted">لا توجد مستندات مرفقة بمعاملات بعد.</p>
                                        @permission('transactions.create')
                                            <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-sm">إنشاء معاملة ورفع مستندات</a>
                                        @endpermission
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="padding: 1rem 1.5rem;">{{ $attachments->links() }}</div>
        </div>
    </div>
</x-app-layout>
