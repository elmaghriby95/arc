<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        <div class="page-header">
            <div>
                <nav class="reports-breadcrumb">
                    <a href="{{ route('reports.index') }}">{{ __('reports.title') }}</a>
                    <span>/</span>
                    <span>{{ $reportType->label() }}</span>
                </nav>
                <h1 class="page-title">{{ $reportType->label() }}</h1>
                <p class="page-subtitle">{{ $reportType->description() }}</p>
            </div>
        </div>
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">{{ __('reports.filters_title') }}</h3></div>
        <div class="card-body">
            @include('reports.partials.filters', ['showStaleDays' => true, 'showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.stale_transactions.label') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.avg_stale_days') }}</span>
                <span class="stat-value">{{ $data['avg_days'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.max_stale_days') }}</span>
                <span class="stat-value">{{ $data['max_days'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.stale_threshold') }}</span>
                <span class="stat-value">{{ $data['stale_days'] }}+</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_department') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('common.department') }}</th><th>{{ __('reports.count') }}</th><th>{{ __('reports.avg_days') }}</th></tr></thead>
                    <tbody>
                        @forelse ($data['by_department'] as $row)
                            <tr>
                                <td>{{ $row['department'] }}</td>
                                <td><strong>{{ $row['count'] }}</strong></td>
                                <td>{{ __('reports.days_count', ['count' => $row['avg_days']]) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="table-empty">{{ __('reports.no_stale') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_status') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('common.status') }}</th><th>{{ __('reports.count') }}</th></tr></thead>
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
            <h3 class="card-title">{{ __('reports.details') }}</h3>
            <p class="card-subtitle">{{ __('reports.transaction_count', ['count' => $data['details']->count()]) }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('common.reference_number') }}</th>
                        <th>{{ __('common.title') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('reports.stale_days_col') }}</th>
                        <th>{{ __('reports.creator') }}</th>
                        <th>{{ __('reports.last_activity') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td><span class="reports-inline-status" style="--status-color: {{ $row['status_color'] ?? '#6366f1' }}">{{ $row['status'] }}</span></td>
                            <td><span class="reports-stale-badge">{{ __('reports.days_count', ['count' => $row['days_stale']]) }}</span></td>
                            <td>{{ $row['creator'] }}</td>
                            <td class="text-muted">{{ $row['last_activity'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">{{ __('reports.no_stale_filtered') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
