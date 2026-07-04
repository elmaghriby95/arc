<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <a href="{{ route('documents.index') }}" class="settings-back-link">← العودة للوثائق</a>
                <h2 class="page-title">{{ $attachment->displayName() }}</h2>
            </div>
            <div class="form-actions">
                @permission('documents.download')
                    <a href="{{ route('documents.download', $attachment) }}" class="btn btn-secondary">تحميل</a>
                @endpermission
                @permission('transactions.view')
                    <a href="{{ route('transactions.show', $attachment->transaction) }}" class="btn btn-primary">عرض المعاملة</a>
                @endpermission
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">بيانات المستند</h3>
            </div>
            <div class="card-body">
                <dl class="dl-grid">
                    <div><dt>اسم الملف</dt><dd>{{ $attachment->effectiveFileName() ?? '—' }}</dd></div>
                    <div><dt>الرقم الإشاري</dt><dd><code class="txn-ref">{{ $attachment->reference_number ?? $attachment->transaction->reference_number }}</code></dd></div>
                    <div><dt>نوع الملف</dt><dd>{{ strtoupper($attachment->fileKind()) }}</dd></div>
                    <div><dt>حجم الملف</dt><dd>{{ $attachment->formattedSize() }}</dd></div>
                    <div><dt>رفع بواسطة</dt><dd>{{ $attachment->uploader?->name ?? '—' }}</dd></div>
                    <div><dt>تاريخ الإضافة</dt><dd>{{ $attachment->created_at->format('Y-m-d H:i') }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">المعاملة المرتبطة</h3>
            </div>
            <div class="card-body">
                <dl class="dl-grid">
                    <div><dt>الرقم الإشاري</dt><dd><code class="txn-ref">{{ $attachment->transaction->reference_number }}</code></dd></div>
                    <div><dt>عنوان المعاملة</dt><dd>{{ $attachment->transaction->title }}</dd></div>
                    <div><dt>القسم</dt><dd>{{ $attachment->transaction->department?->name ?? '—' }}</dd></div>
                    <div><dt>نوع المعاملة</dt><dd>{{ $attachment->transaction->transactionType?->name ?? '—' }}</dd></div>
                    <div><dt>حالة المعاملة</dt><dd><x-transaction-status-badge :status="$attachment->transaction->status" /></dd></div>
                    <div><dt>تاريخ المعاملة</dt><dd>{{ $attachment->transaction->transaction_date?->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
