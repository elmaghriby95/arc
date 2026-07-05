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
        <div class="stat-card stat-card--orange">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.attachment_refs') }}</span>
                <span class="stat-value">{{ $data['total_attachment_refs'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.transaction_refs') }}</span>
                <span class="stat-value">{{ $data['total_transaction_refs'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--amber">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.duplicate_refs') }}</span>
                <span class="stat-value">{{ $data['duplicate_count'] }}</span>
            </div>
        </div>
    </div>

    @if ($data['duplicates']->isNotEmpty())
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.duplicate_list') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('reports.reference_number') }}</th><th>{{ __('reports.duplicate_count') }}</th></tr></thead>
                    <tbody>
                        @foreach ($data['duplicates'] as $row)
                            <tr>
                                <td><code>{{ $row['reference_number'] }}</code></td>
                                <td><span class="reports-duplicate-badge">{{ $row['count'] }}×</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card card-elevated">
        <div class="card-header">
            <h3 class="card-title">{{ __('reports.details') }}</h3>
            <p class="card-subtitle">{{ __('reports.record_count', ['count' => $data['details']->count()]) }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('reports.reference_number') }}</th>
                        <th>{{ __('reports.year') }}</th>
                        <th>{{ __('reports.month') }}</th>
                        <th>{{ __('common.title') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('reports.uploader') }}</th>
                        <th>{{ __('common.date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data['details'] as $row)
                        <tr>
                            <td>
                                <code>{{ $row['reference_number'] }}</code>
                                @if ($row['is_duplicate'])
                                    <span class="reports-duplicate-badge">{{ __('reports.duplicate') }}</span>
                                @endif
                            </td>
                            <td>{{ $row['year'] }}</td>
                            <td>{{ $row['month'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['department'] }}</td>
                            <td>{{ $row['uploader'] }}</td>
                            <td class="text-muted">{{ $row['date'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
