<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <nav class="reports-breadcrumb">
                    <a href="{{ route('reports.index') }}">التقارير</a>
                    <span>/</span>
                    <span>{{ $reportType->label() }}</span>
                </nav>
                <h1 class="page-title">{{ $reportType->label() }}</h1>
                <p class="page-subtitle">{{ $reportType->description() }}</p>
            </div>
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">فلاتر التقرير</h3></div>
        <div class="card-body">
            @include('reports.partials.filters')
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-body">
                <span class="stat-label">إجمالي الحركات</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">حسب الإجراء</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>الإجراء</th><th>العدد</th></tr></thead>
                    <tbody>
                        @foreach ($data['by_action'] as $row)
                            <tr><td>{{ $row['label'] }}</td><td><strong>{{ $row['count'] }}</strong></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">أكثر المستخدمين نشاطاً</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>المستخدم</th><th>العدد</th></tr></thead>
                    <tbody>
                        @foreach ($data['by_user'] as $row)
                            <tr><td>{{ $row['user'] }}</td><td><strong>{{ $row['count'] }}</strong></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">سجل الحركات</h3>
            <p class="card-subtitle">{{ $data['details']->count() }} سجل</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الرقم المرجعي</th>
                        <th>العنوان</th>
                        <th>القسم</th>
                        <th>من</th>
                        <th>إلى</th>
                        <th>الإجراء</th>
                        <th>بواسطة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td class="text-muted">{{ $row['date'] }}</td>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['from_status'] }}</td>
                            <td><span class="reports-inline-status" style="--status-color: {{ $row['to_status_color'] ?? '#6366f1' }}">{{ $row['to_status'] }}</span></td>
                            <td>{{ $row['action'] }}</td>
                            <td>{{ $row['changed_by'] }}</td>
                            <td class="text-muted">{{ Str::limit($row['notes'], 40) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-empty">لا توجد حركات مطابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
