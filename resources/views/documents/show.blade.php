<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <h2 class="page-title">{{ $document->title }}</h2>
            <div class="form-actions">
                @permission('documents.download')
                    <a href="{{ route('documents.download', $document) }}" class="btn btn-secondary">تحميل</a>
                @endpermission
                @permission('documents.edit')
                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">تعديل</a>
                @endpermission
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-body">
                <dl class="dl-grid">
                    <div><dt>الرقم المرجعي</dt><dd>{{ $document->reference_number }}</dd></div>
                    <div><dt>الحالة</dt><dd>{{ $document->status->label() }}</dd></div>
                    <div><dt>القسم</dt><dd>{{ $document->department?->name ?? '—' }}</dd></div>
                    <div><dt>التصنيف</dt><dd>{{ $document->category?->name ?? '—' }}</dd></div>
                    <div><dt>تاريخ الوثيقة</dt><dd>{{ $document->document_date?->format('Y-m-d') ?? '—' }}</dd></div>
                    <div><dt>رفع بواسطة</dt><dd>{{ $document->uploader->name }}</dd></div>
                    <div><dt>اسم الملف</dt><dd>{{ $document->file_name }}</dd></div>
                    <div><dt>حجم الملف</dt><dd>{{ number_format($document->file_size / 1024, 2) }} KB</dd></div>
                    <div style="grid-column:1/-1;"><dt>الوصف</dt><dd>{{ $document->description ?? '—' }}</dd></div>
                    <div style="grid-column:1/-1;">
                        <dt>الكلمات المفتاحية</dt>
                        <dd>{{ $document->tags->isEmpty() ? '—' : $document->tags->pluck('name')->join('، ') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">سجل الإصدارات</h3>
            </div>
            <div class="card-body">
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>الإصدار</th>
                                <th>الملف</th>
                                <th>بواسطة</th>
                                <th>ملاحظة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->versions as $version)
                                <tr>
                                    <td>{{ $version->version_number }}</td>
                                    <td>{{ $version->file_name }}</td>
                                    <td>{{ $version->uploader->name }}</td>
                                    <td>{{ $version->change_note ?? '—' }}</td>
                                    <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
