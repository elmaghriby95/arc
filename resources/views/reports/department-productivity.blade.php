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
            @include('reports.partials.filters', ['showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">الأقسام</span>
                <span class="stat-value">{{ $data['totals']['departments'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">معاملات منشأة</span>
                <span class="stat-value">{{ $data['totals']['created'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-body">
                <span class="stat-label">مؤرشفة</span>
                <span class="stat-value">{{ $data['totals']['archived'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">قيد الإجراء</span>
                <span class="stat-value">{{ $data['totals']['in_progress'] }}</span>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">مقارنة الأقسام</h3>
            <p class="card-subtitle">إنتاجية وإنجاز المعاملات</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>القسم</th>
                        <th>منشأة</th>
                        <th>مسودة</th>
                        <th>قيد الإجراء</th>
                        <th>مؤرشفة</th>
                        <th>مكتملة بالفترة</th>
                        <th>نسبة الإنجاز</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><strong>{{ $row['department'] }}</strong></td>
                            <td>{{ $row['created'] }}</td>
                            <td>{{ $row['draft'] }}</td>
                            <td>{{ $row['in_progress'] }}</td>
                            <td>{{ $row['archived'] }}</td>
                            <td>{{ $row['completed_in_period'] }}</td>
                            <td>
                                <div class="reports-progress">
                                    <div class="reports-progress-bar" style="width: {{ min(100, $row['completion_rate']) }}%"></div>
                                    <span>{{ $row['completion_rate'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">لا توجد بيانات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
