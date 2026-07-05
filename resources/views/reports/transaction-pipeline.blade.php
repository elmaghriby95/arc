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
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('reports.filters_title') }}</h3>
                <p class="card-subtitle">{{ __('reports.filters_subtitle') }}</p>
            </div>
        </div>
        <div class="card-body">
            @include('reports.partials.filters', ['showStaleDays' => false])
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        @foreach ($data['summary'] as $item)
            <div class="stat-card stat-card--indigo">
                <div class="stat-card-icon" style="background: {{ $item['color'] }}22; color: {{ $item['color'] }}">
                    <span class="reports-status-dot" style="background: {{ $item['color'] }}"></span>
                </div>
                <div class="stat-card-body">
                    <span class="stat-label">{{ $item['name'] }}</span>
                    <span class="stat-value">{{ $item['count'] }}</span>
                </div>
            </div>
        @endforeach
        <div class="stat-card stat-card--violet">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('common.total') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
    </div>

    @if (! empty($data['by_department']))
        <div class="card card-elevated">
            <div class="card-header">
                <h3 class="card-title">{{ __('reports.by_department') }}</h3>
            </div>
            <div class="card-body card-body-flush">
                <div class="table-wrapper">
                    <table class="table table-modern reports-matrix-table">
                        <thead>
                            <tr>
                                <th>{{ __('common.department') }}</th>
                                @foreach ($data['statuses'] as $status)
                                    <th>{{ $status->name }}</th>
                                @endforeach
                                <th>{{ __('reports.sum') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['by_department'] as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong></td>
                                    @foreach ($data['statuses'] as $status)
                                        @php $cell = $row['cells'][$status->name] ?? null; @endphp
                                        <td>
                                            @if ($cell && $cell['count'] > 0)
                                                <span class="reports-count-badge" style="--badge-color: {{ $cell['color'] }}">{{ $cell['count'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td><strong>{{ $row['total'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if (! empty($data['by_type']))
        <div class="card card-elevated">
            <div class="card-header">
                <h3 class="card-title">{{ __('reports.by_type') }}</h3>
            </div>
            <div class="card-body card-body-flush">
                <div class="table-wrapper">
                    <table class="table table-modern reports-matrix-table">
                        <thead>
                            <tr>
                                <th>{{ __('common.transaction_type') }}</th>
                                @foreach ($data['statuses'] as $status)
                                    <th>{{ $status->name }}</th>
                                @endforeach
                                <th>{{ __('reports.sum') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['by_type'] as $row)
                                <tr>
                                    <td><strong>{{ $row['label'] }}</strong></td>
                                    @foreach ($data['statuses'] as $status)
                                        @php $cell = $row['cells'][$status->name] ?? null; @endphp
                                        <td>
                                            @if ($cell && $cell['count'] > 0)
                                                <span class="reports-count-badge" style="--badge-color: {{ $cell['color'] }}">{{ $cell['count'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td><strong>{{ $row['total'] }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('reports.details') }}</h3>
                <p class="card-subtitle">{{ __('reports.transaction_count', ['count' => $data['details']->count()]) }}</p>
            </div>
        </div>
        <div class="card-body card-body-flush">
            <div class="table-wrapper">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>{{ __('common.reference_number') }}</th>
                            <th>{{ __('common.title') }}</th>
                            <th>{{ __('common.department') }}</th>
                            <th>{{ __('common.transaction_type') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('reports.creator') }}</th>
                            <th>{{ __('common.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['details'] as $row)
                            <tr>
                                <td><code>{{ $row['reference_number'] }}</code></td>
                                <td>{{ $row['title'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['type'] }}</td>
                                <td>
                                    <span class="reports-inline-status" style="--status-color: {{ $row['status_color'] ?? '#6366f1' }}">{{ $row['status'] }}</span>
                                </td>
                                <td>{{ $row['creator'] }}</td>
                                <td class="text-muted">{{ $row['date'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="table-empty">{{ __('reports.no_data') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
