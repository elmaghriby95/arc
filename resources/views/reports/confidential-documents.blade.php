<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        @include('reports.partials.show-header')
    </x-slot>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">{{ __('reports.filters_title') }}</h3></div>
        <div class="card-body">
            @include('reports.partials.filters', ['showTypeFilter' => false, 'showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--rose">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.confidential_documents.label') }}</span>
                <span class="stat-value">{{ $data['total_documents'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.linked_attachments') }}</span>
                <span class="stat-value">{{ $data['total_linked_attachments'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.sum') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">{{ __('reports.confidential_list') }}</h3>
            <p class="card-subtitle">{{ __('reports.record_count', ['count' => $data['details']->count()]) }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('reports.source') }}</th>
                        <th>{{ __('common.title') }}</th>
                        <th>{{ __('common.reference_number') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('reports.uploader') }}</th>
                        <th>{{ __('common.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td><span class="reports-confidential-badge">{{ $row['source'] }}</span></td>
                            <td>{{ $row['title'] }}</td>
                            <td><code>{{ $row['reference_number'] }}</code></td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['uploader'] }}</td>
                            <td class="text-muted">{{ $row['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">{{ __('reports.no_confidential') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
