<x-app-layout>
    <x-slot name="header">
        <div class="page-header txn-page-header">
            <div class="txn-page-header-main">
                <a href="{{ route('transactions.index') }}" class="settings-back-link">← العودة للمعاملات</a>
                <div class="txn-page-header-row">
                    <div>
                        <h2 class="page-title">{{ $transaction->title }}</h2>
                        <div class="txn-page-meta">
                            <code class="txn-ref">{{ $transaction->reference_number }}</code>
                            <x-transaction-status-badge :status="$transaction->status" />
                        </div>
                    </div>
                    @permission('transactions.edit')
                        @unless ($transaction->isAtFinalStatus())
                            <a href="{{ route('transactions.edit', $transaction) }}" class="btn btn-secondary">تعديل البيانات</a>
                        @endunless
                    @endpermission
                </div>
            </div>
        </div>
    </x-slot>

    <div class="container txn-page">
        <div class="txn-layout">
            @include('transactions.partials.attachments-sidebar', [
                'transaction' => $transaction,
                'canManageAttachments' => $canManageAttachments,
                'availableDocuments' => $availableDocuments,
            ])

            <div class="txn-main">
                <section class="txn-hero card">
                    <div class="txn-hero-grid">
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">نوع المعاملة</span>
                            <strong>{{ $transaction->transactionType?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">الوحدة التنظيمية</span>
                            <strong>{{ $transaction->department?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">المجلد</span>
                            <strong>{{ $transaction->folder?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">تاريخ المعاملة</span>
                            <strong>{{ $transaction->transaction_date?->format('Y-m-d') ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">أنشأها</span>
                            <strong>{{ $transaction->creator?->name ?? '—' }}</strong>
                        </div>
                        <div class="txn-hero-item">
                            <span class="txn-hero-label">عدد المرفقات</span>
                            <strong>{{ $transaction->attachments->count() }}</strong>
                        </div>
                    </div>
                </section>

                <section class="card txn-workflow-card">
                    <div class="card-header">
                        <h3 class="card-title">مسار سير العمل</h3>
                    </div>
                    <div class="card-body">
                        <div class="txn-workflow-steps txn-workflow-steps--compact">
                            @foreach ($workflow as $index => $step)
                                @php
                                    $isCurrent = $transaction->transaction_status_id === $step->id;
                                    $isPast = $step->sort_order < ($transaction->status?->sort_order ?? 0)
                                        || ($step->sort_order === ($transaction->status?->sort_order ?? 0) && $step->id <= $transaction->transaction_status_id);
                                @endphp
                                <div class="txn-workflow-step {{ $isCurrent ? 'txn-workflow-step--current' : '' }} {{ $isPast && ! $isCurrent ? 'txn-workflow-step--done' : '' }}">
                                    <span class="txn-workflow-step-num">{{ $index + 1 }}</span>
                                    <span class="txn-status-badge" style="--txn-status-color: {{ $step->color ?? '#64748b' }}">{{ $step->name }}</span>
                                </div>
                                @if (! $loop->last)
                                    <span class="txn-workflow-arrow" aria-hidden="true">←</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </section>

                <div class="txn-panels">
                    <section class="card txn-panel">
                        <div class="card-header">
                            <h3 class="card-title">تفاصيل المعاملة</h3>
                        </div>
                        <div class="card-body">
                            @if ($transaction->description)
                                <div class="txn-detail-block">
                                    <h4>الوصف</h4>
                                    <p>{{ $transaction->description }}</p>
                                </div>
                            @endif
                            @if ($transaction->notes)
                                <div class="txn-detail-block">
                                    <h4>ملاحظات</h4>
                                    <p>{{ $transaction->notes }}</p>
                                </div>
                            @endif
                            @unless ($transaction->description || $transaction->notes)
                                <p class="text-muted">لا توجد تفاصيل إضافية.</p>
                            @endunless
                            <dl class="dl-grid txn-meta-grid">
                                <div><dt>مسار الوحدة</dt><dd>{{ $transaction->department?->breadcrumb() ?? '—' }}</dd></div>
                                <div><dt>تاريخ الإنشاء</dt><dd>{{ $transaction->created_at->format('Y-m-d H:i') }}</dd></div>
                            </dl>
                        </div>
                    </section>

                    <section class="card txn-panel txn-panel--action">
                        <div class="card-header">
                            <h3 class="card-title">تغيير الحالة</h3>
                        </div>
                        <div class="card-body">
                            @if ($nextStatus && $canAdvance)
                                <div class="txn-advance-box">
                                    <p class="txn-advance-next">الحالة التالية</p>
                                    <x-transaction-status-badge :status="$nextStatus" />
                                    @if ($nextStatus->required_permission)
                                        <p class="form-hint">مطلوب: {{ $nextStatus->permissionLabel() }}</p>
                                    @endif
                                    <form method="POST" action="{{ route('transactions.advance-status', $transaction) }}" class="txn-advance-form">
                                        @csrf
                                        <div class="form-group">
                                            <x-input-label for="advance_notes" value="ملاحظة (اختياري)" />
                                            <textarea id="advance_notes" name="notes" rows="2" class="form-control" placeholder="سبب الانتقال...">{{ old('notes') }}</textarea>
                                        </div>
                                        <x-primary-button>الانتقال إلى {{ $nextStatus->name }}</x-primary-button>
                                    </form>
                                </div>
                            @elseif ($transaction->isAtFinalStatus())
                                <div class="txn-state-message txn-state-message--success">
                                    <strong>معاملة مكتملة</strong>
                                    <p>المعاملة في الحالة النهائية.</p>
                                </div>
                            @elseif ($nextStatus)
                                <div class="txn-state-message txn-state-message--warning">
                                    <strong>بانتظار صلاحية</strong>
                                    <p>لا تملك صلاحية الانتقال إلى: {{ $nextStatus->name }}</p>
                                    @if ($nextStatus->required_permission)
                                        <p class="form-hint">مطلوب: {{ $nextStatus->permissionLabel() }}</p>
                                    @endif
                                </div>
                            @else
                                <p class="text-muted">لا توجد حالة تالية.</p>
                            @endif
                        </div>
                    </section>
                </div>

                @if ($transaction->statusHistories->isNotEmpty())
                    <section class="card">
                        <div class="card-header">
                            <h3 class="card-title">سجل تغيير الحالات</h3>
                        </div>
                        <div class="card-body card-body-flush">
                            <div class="table-wrapper">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>من</th>
                                            <th>إلى</th>
                                            <th>بواسطة</th>
                                            <th>ملاحظة</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($transaction->statusHistories as $history)
                                            <tr>
                                                <td>{{ $history->fromStatus?->name ?? '—' }}</td>
                                                <td><x-transaction-status-badge :status="$history->toStatus" /></td>
                                                <td>{{ $history->changedBy?->name ?? '—' }}</td>
                                                <td>{{ $history->notes ?? '—' }}</td>
                                                <td>{{ $history->created_at?->format('Y-m-d H:i') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endif

                @permission('transactions.delete')
                    <form method="POST" action="{{ route('transactions.destroy', $transaction) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه المعاملة؟')" class="txn-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">حذف المعاملة</button>
                    </form>
                @endpermission
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/transaction-attachments.js') }}"></script>
    @endpush
</x-app-layout>
