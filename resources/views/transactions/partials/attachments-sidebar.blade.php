<aside class="txn-sidebar" data-txn-sidebar>
    <div class="txn-sidebar-inner">
        <div class="txn-sidebar-header">
            <div>
                <h3 class="txn-sidebar-title">مستندات المعاملة</h3>
                <p class="txn-sidebar-subtitle">{{ $transaction->attachments->count() }} مرفق</p>
            </div>
            @if ($canManageAttachments)
                <button type="button" class="btn btn-secondary btn-sm" data-modal-open="link-document" title="ربط من الأرشيف">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </button>
            @endif
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
                        <div class="txn-attachment-meta">
                            <span>{{ $attachment->formattedSize() }}</span>
                            @if ($attachment->isFromArchive())
                                <span class="txn-attachment-badge">من الأرشيف</span>
                            @endif
                        </div>
                        <div class="txn-attachment-actions">
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
                        <small>ارفع ملفات أو اربط مستنداً من الأرشيف</small>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</aside>

@if ($canManageAttachments)
    <div class="modal" id="modal-link-document">
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-dialog modal-dialog-lg">
            <div class="modal-content txn-link-modal">
                <div class="modal-header">
                    <h3 class="modal-title">ربط مستند من الأرشيف</h3>
                    <button type="button" class="modal-close" data-modal-close aria-label="إغلاق">&times;</button>
                </div>
                <form method="POST" action="{{ route('transactions.attachments.link', $transaction) }}">
                    @csrf
                    <div class="modal-body">
                        @if ($availableDocuments->isEmpty())
                            <p class="text-muted">لا توجد وثائق متاحة للربط في نطاقك التنظيمي.</p>
                        @else
                            <div class="form-group">
                                <x-input-label for="document_id" value="اختر وثيقة من الأرشيف" />
                                <select id="document_id" name="document_id" class="form-select" required>
                                    <option value="">— اختر —</option>
                                    @foreach ($availableDocuments as $document)
                                        <option value="{{ $document->id }}">{{ $document->title }} ({{ $document->reference_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="form-hint">تظهر الوثائق ضمن نفس الهيكل التنظيمي فقط</p>
                        @endif
                    </div>
                    @if ($availableDocuments->isNotEmpty())
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-modal-close>إلغاء</button>
                            <x-primary-button>ربط المستند</x-primary-button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endif
