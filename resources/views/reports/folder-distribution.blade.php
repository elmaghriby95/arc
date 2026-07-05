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
        <div class="stat-card stat-card--teal">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.total_transactions') }}</span>
                <span class="stat-value">{{ $data['total_transactions'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.used_folders') }}</span>
                <span class="stat-value">{{ $data['total_folders'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.without_folder') }}</span>
                <span class="stat-value">{{ $data['without_folder'] }}</span>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">{{ __('reports.by_folder') }}</h3></div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('common.folder') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('reports.transactions') }}</th>
                        <th>{{ __('reports.attachments') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['by_folder'] as $row)
                        <tr>
                            <td><strong>{{ $row['folder'] }}</strong></td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['transactions'] }}</td>
                            <td>{{ $row['attachments'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
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
                        <th>{{ __('common.folder') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('common.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['folder'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td><span class="reports-inline-status" style="--status-color: {{ $row['status_color'] ?? '#6366f1' }}">{{ $row['status'] }}</span></td>
                            <td class="text-muted">{{ $row['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
