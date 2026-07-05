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
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">إجمالي المرفقات</span>
                <span class="stat-value">{{ $data['total_count'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">حجم التخزين</span>
                <span class="stat-value reports-stat-compact">{{ $data['total_size_formatted'] }}</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">حسب نوع الملف</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>النوع</th><th>العدد</th><th>الحجم</th></tr></thead>
                    <tbody>
                        @foreach ($data['by_kind'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>{{ $row['size_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">حسب القسم</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>القسم</th><th>العدد</th><th>الحجم</th></tr></thead>
                    <tbody>
                        @forelse ($data['by_department'] as $row)
                            <tr>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>{{ $row['size_formatted'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="table-empty">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">التفاصيل</h3>
            <p class="card-subtitle">{{ $data['details']->count() }} مرفق</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>المعاملة</th>
                        <th>القسم</th>
                        <th>نوع الملف</th>
                        <th>الحجم</th>
                        <th>الرافع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td>{{ $row['title'] }}</td>
                            <td><code>{{ $row['transaction'] }}</code></td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['kind'] }}</td>
                            <td>{{ $row['size'] }}</td>
                            <td>{{ $row['uploader'] }}</td>
                            <td class="text-muted">{{ $row['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">لا توجد مرفقات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
