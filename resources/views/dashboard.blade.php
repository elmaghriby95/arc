<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('Dashboard') }}</h1>
                <p class="page-subtitle">
                    مرحباً {{ Auth::user()->name }}
                    @if ($orgBreadcrumb)
                        — <span class="org-path org-path--inline">{{ $orgBreadcrumb }}</span>
                    @endif
                </p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    إنشاء معاملة
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="dashboard-hero">
        <div class="dashboard-hero-content">
            <h2>منظومة الأرشفة الإلكترونية</h2>
            <p>إدارة المعاملات ومستنداتها المرفقة مع بحث سريع وتتبع سير العمل.</p>
        </div>
        <div class="dashboard-hero-badge">
            <span>{{ $stats['documents'] }}</span>
            <small>مستند مرفق</small>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-label">إجمالي الوثائق</span>
                <span class="stat-value">{{ $stats['documents'] }}</span>
            </div>
        </div>

        <div class="stat-card stat-card--cyan">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/></svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-label">الأقسام النشطة</span>
                <span class="stat-value">{{ $stats['departments'] }}</span>
            </div>
        </div>

        <div class="stat-card stat-card--violet">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-label">التصنيفات</span>
                <span class="stat-value">{{ $stats['categories'] }}</span>
            </div>
        </div>

        @if ($stats['users'] !== null)
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
            </div>
            <div class="stat-card-body">
                <span class="stat-label">المستخدمون</span>
                <span class="stat-value">{{ $stats['users'] }}</span>
            </div>
        </div>
        @endif
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">أحدث الوثائق</h3>
                <p class="card-subtitle">آخر المستندات المرفقة بالمعاملات</p>
            </div>
            <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">عرض الكل</a>
        </div>
        <div class="card-body card-body-flush">
            @if ($recentAttachments->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                    </div>
                    <p>لا توجد مستندات بعد. أنشئ معاملة وارفع المستندات المطلوبة.</p>
                    @permission('transactions.create')
                        <a href="{{ route('transactions.create') }}" class="btn btn-primary">إنشاء معاملة</a>
                    @endpermission
                </div>
            @else
                <div class="table-wrapper">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>المستند</th>
                                <th>المعاملة</th>
                                <th>القسم</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentAttachments as $attachment)
                                <tr>
                                    <td>
                                        <a href="{{ route('documents.show', $attachment) }}" class="table-title">{{ $attachment->displayName() }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('transactions.show', $attachment->transaction) }}" class="ref-pill">{{ $attachment->transaction->reference_number }}</a>
                                    </td>
                                    <td>{{ $attachment->transaction->department?->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $attachment->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
