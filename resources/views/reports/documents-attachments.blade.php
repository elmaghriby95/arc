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
            @include('reports.partials.filters', ['showStatusFilter' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.total_attachments') }}</span>
                <span class="stat-value">{{ $data['total_count'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.storage_size') }}</span>
                <span class="stat-value reports-stat-compact">{{ $data['total_size_formatted'] }}</span>
            </div>
        </div>
    </div>

    <div class="reports-split-grid">
        <div class="card card-elevated">
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_file_kind') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('reports.kind') }}</th><th>{{ __('reports.count') }}</th><th>{{ __('reports.size') }}</th></tr></thead>
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
            <div class="card-header"><h3 class="card-title">{{ __('reports.by_department') }}</h3></div>
            <div class="card-body card-body-flush">
                <table class="table table-modern">
                    <thead><tr><th>{{ __('common.department') }}</th><th>{{ __('reports.count') }}</th><th>{{ __('reports.size') }}</th></tr></thead>
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
            <h3 class="card-title">{{ __('reports.details') }}</h3>
            <p class="card-subtitle">{{ __('reports.attachment_count', ['count' => $data['details']->count()]) }}</p>
        </div>
        <div class="card-body card-body-flush">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>{{ __('common.title') }}</th>
                        <th>{{ __('documents.transaction') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('documents.file_kind') }}</th>
                        <th>{{ __('reports.size') }}</th>
                        <th>{{ __('documents.uploaded_by') }}</th>
                        <th>{{ __('common.date') }}</th>
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
                        <tr><td colspan="7" class="table-empty">{{ __('reports.no_attachments') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
