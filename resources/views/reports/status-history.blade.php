<x-app-layout>
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
            @include('reports.partials.filters')
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.total_movements') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_action') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('transactions.action') }}</th><th>{{ __('reports.count') }}</th></tr></thead>
                    <tbody>
                        @foreach ($data['by_action'] as $row)
                            <tr><td>{{ $row['label'] }}</td><td><strong>{{ $row['count'] }}</strong></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.top_users') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('reports.user') }}</th><th>{{ __('reports.count') }}</th></tr></thead>
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
            <h3 class="card-title">{{ __('reports.movement_log') }}</h3>
            <p class="card-subtitle">{{ __('reports.record_count', ['count' => $data['details']->count()]) }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('common.date') }}</th>
                        <th>{{ __('common.reference_number') }}</th>
                        <th>{{ __('common.title') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('transactions.from_status') }}</th>
                        <th>{{ __('transactions.to_status') }}</th>
                        <th>{{ __('transactions.action') }}</th>
                        <th>{{ __('transactions.changed_by') }}</th>
                        <th>{{ __('common.notes') }}</th>
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
                        <tr><td colspan="9" class="table-empty">{{ __('reports.no_movements') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
