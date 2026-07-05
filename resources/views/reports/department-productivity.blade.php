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
            @include('reports.partials.filters', ['showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.departments') }}</span>
                <span class="stat-value">{{ $data['totals']['departments'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.created_transactions') }}</span>
                <span class="stat-value">{{ $data['totals']['created'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.archived') }}</span>
                <span class="stat-value">{{ $data['totals']['archived'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.in_progress') }}</span>
                <span class="stat-value">{{ $data['totals']['in_progress'] }}</span>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">{{ __('reports.department_comparison') }}</h3>
            <p class="card-subtitle">{{ __('reports.productivity_subtitle') }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('reports.created') }}</th>
                        <th>{{ __('reports.draft') }}</th>
                        <th>{{ __('reports.in_progress') }}</th>
                        <th>{{ __('reports.archived') }}</th>
                        <th>{{ __('reports.completed_in_period') }}</th>
                        <th>{{ __('reports.completion_rate') }}</th>
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
                        <tr><td colspan="7" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
