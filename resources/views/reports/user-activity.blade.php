<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        @include('reports.partials.show-header')
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">{{ __('reports.filters_title') }}</h3></div>
        <div class="card-body">
            @include('reports.partials.filters', ['showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--slate">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.active_users') }}</span>
                <span class="stat-value">{{ $data['total_users'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.total_actions') }}</span>
                <span class="stat-value">{{ $data['total_actions'] }}</span>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">{{ __('reports.user_activity_log') }}</h3>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('reports.user') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('reports.created') }}</th>
                        <th>{{ __('reports.transitions') }}</th>
                        <th>{{ __('reports.uploads') }}</th>
                        <th>{{ __('reports.sum') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><strong>{{ $row['user'] }}</strong></td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['created'] }}</td>
                            <td>{{ $row['transitions'] }}</td>
                            <td>{{ $row['uploads'] }}</td>
                            <td><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">{{ __('reports.no_activity') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
