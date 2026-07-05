<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        @include('reports.partials.show-header')
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">{{ __('reports.filters_title') }}</h3>
        </div>
        <div class="card-body">
            @include('reports.partials.filters', ['showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--rose">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.completed_transactions') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.avg_completion_days') }}</span>
                <span class="stat-value">{{ $data['avg_days'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.max_completion_days') }}</span>
                <span class="stat-value">{{ $data['max_days'] }}</span>
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
                                <td>{{ $row['count'] }}</td>
                                <td>{{ __('reports.days_count', ['count' => $row['avg_days']]) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_type') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('common.transaction_type') }}</th><th>{{ __('reports.count') }}</th><th>{{ __('reports.avg_days') }}</th></tr></thead>
                    <tbody>
                        @forelse ($data['by_type'] as $row)
                            <tr>
                                <td>{{ $row['type'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>{{ __('reports.days_count', ['count' => $row['avg_days']]) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="table-empty">{{ __('reports.no_data') }}</td></tr>
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
                        <th>{{ __('common.transaction_type') }}</th>
                        <th>{{ __('reports.total_days') }}</th>
                        <th>{{ __('reports.created_at') }}</th>
                        <th>{{ __('reports.archived_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['type'] }}</td>
                            <td><strong>{{ __('reports.days_count', ['count' => $row['total_days']]) }}</strong></td>
                            <td class="text-muted">{{ $row['created_at'] }}</td>
                            <td class="text-muted">{{ $row['archived_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">{{ __('reports.no_completed') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
