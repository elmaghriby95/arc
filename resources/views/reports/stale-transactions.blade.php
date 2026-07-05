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
            @include('reports.partials.filters', ['showStaleDays' => true, 'showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">معاملات متأخرة</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">متوسط أيام التوقف</span>
                <span class="stat-value">{{ $data['avg_days'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">أقصى توقف (يوم)</span>
                <span class="stat-value">{{ $data['max_days'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">حد التأخير</span>
                <span class="stat-value">{{ $data['stale_days'] }}+</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">حسب القسم</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>القسم</th><th>العدد</th><th>متوسط الأيام</th></tr></thead>
                    <tbody>
                        @forelse ($data['by_department'] as $row)
                            <tr>
                                <td>{{ $row['department'] }}</td>
                                <td><strong>{{ $row['count'] }}</strong></td>
                                <td>{{ $row['avg_days'] }} يوم</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="table-empty">لا توجد معاملات متأخرة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">حسب الحالة</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>الحالة</th><th>العدد</th></tr></thead>
                    <tbody>
                        @forelse ($data['by_status'] as $row)
                            <tr>
                                <td><span class="reports-inline-status" style="--status-color: {{ $row['color'] ?? '#6366f1' }}">{{ $row['status'] }}</span></td>
                                <td><strong>{{ $row['count'] }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="table-empty">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">التفاصيل</h3>
            <p class="card-subtitle">{{ $data['details']->count() }} معاملة</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>الرقم المرجعي</th>
                        <th>العنوان</th>
                        <th>القسم</th>
                        <th>الحالة</th>
                        <th>أيام التوقف</th>
                        <th>المنشئ</th>
                        <th>آخر نشاط</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td><span class="reports-inline-status" style="--status-color: {{ $row['status_color'] ?? '#6366f1' }}">{{ $row['status'] }}</span></td>
                            <td><span class="reports-stale-badge">{{ $row['days_stale'] }} يوم</span></td>
                            <td>{{ $row['creator'] }}</td>
                            <td class="text-muted">{{ $row['last_activity'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">لا توجد معاملات متأخرة ضمن الفلاتر المحددة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
