<aside class="txn-sidebar" data-txn-sidebar>
    <div class="txn-sidebar-inner">
        <div class="txn-sidebar-header">
            <div>
                <h3 class="txn-sidebar-title">مستندات المعاملة</h3>
                <p class="txn-sidebar-subtitle">{{ $transaction->attachments->count() }} مستند</p>
            </div>
        </div>

        @if ($canManageAttachments)
            <form method="POST"
                  action="{{ route('transactions.attachments.upload', $transaction) }}"
                  enctype="multipart/form-data"
                  class="txn-dropzone"
                  data-txn-dropzone>
                @csrf
                <input type="file"
                       name="files[]"
                       class="txn-dropzone-input"
                       data-txn-file-input
                       multiple
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.bmp,image/*,application/pdf">
                <div class="txn-dropzone-content" data-txn-dropzone-content>
                    <div class="txn-dropzone-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    </div>
                    <p class="txn-dropzone-title">اسحب الملفات وأفلتها هنا</p>
                    <p class="txn-dropzone-hint">PDF · Word · Excel · صور</p>
                    <button type="button" class="btn btn-primary btn-sm" data-txn-browse>اختيار ملفات</button>
                </div>
                <div class="txn-dropzone-queue is-hidden" data-txn-queue>
                    <ul class="txn-dropzone-files" data-txn-file-list></ul>
                    <button type="submit" class="btn btn-primary btn-block" data-txn-upload-btn disabled>رفع الملفات</button>
                </div>
            </form>
        @endif

        <div class="txn-attachments-list">
            @forelse ($transaction->attachments as $attachment)
                <article class="txn-attachment-card txn-attachment-card--{{ $attachment->fileKind() }}">
                    <div class="txn-attachment-preview">
                        @if ($attachment->isImage() && $attachment->fileExists())
                            <a href="{{ route('transactions.attachments.preview', [$transaction, $attachment]) }}" target="_blank" class="txn-attachment-thumb">
                                <img src="{{ route('transactions.attachments.preview', [$transaction, $attachment]) }}" alt="{{ $attachment->displayName() }}" loading="lazy">
                            </a>
                        @else
                            <div class="txn-attachment-icon-wrap">
                                <x-transaction-file-icon :kind="$attachment->fileKind()" />
                            </div>
                        @endif
                    </div>
                    <div class="txn-attachment-body">
                        <h4 class="txn-attachment-name" title="{{ $attachment->displayName() }}">{{ $attachment->displayName() }}</h4>
                        @if ($attachment->reference_number)
                            <div class="txn-attachment-ref">
                                <code>{{ $attachment->reference_number }}</code>
                                @if ($attachment->is_operational_number)
                                    <span class="txn-attachment-ref-tag">تشغيلي</span>
                                @endif
                            </div>
                        @endif
                        <div class="txn-attachment-meta">
                            <span>{{ $attachment->formattedSize() }}</span>
                        </div>
                        <div class="txn-attachment-actions">
                            @permission('documents.view')
                                <a href="{{ route('documents.show', $attachment) }}" class="txn-attachment-action" title="عرض">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            @endpermission
                            <a href="{{ route('transactions.attachments.download', [$transaction, $attachment]) }}" class="txn-attachment-action" title="تحميل">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                            </a>
                            @if ($canManageAttachments)
                                <form method="POST" action="{{ route('transactions.attachments.destroy', [$transaction, $attachment]) }}" onsubmit="return confirm('حذف هذا المرفق؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="txn-attachment-action txn-attachment-action--danger" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H9a2 2 0 01-2-2V7h10z"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="txn-attachments-empty">
                    <x-transaction-file-icon kind="file" />
                    <p>لا توجد مستندات مرفقة بعد</p>
                    @if ($canManageAttachments)
                        <small>ارفع الملفات المطلوبة للمعاملة</small>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</aside>
