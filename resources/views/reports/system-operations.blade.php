<x-app-layout>
    @include('reports.partials.styles')

    <x-slot name="header">
        @include('reports.partials.show-header')
    </x-slot>

    <div class="ops-hero">
        <div class="ops-hero-content">
            <p class="ops-hero-eyebrow">{{ __('reports.phase_3') }}</p>
            <h2>{{ __('reports.ops.hero_title') }}</h2>
            <p>{{ __('reports.ops.hero_desc') }}</p>
        </div>
        <div class="ops-hero-meta">
            <div>
                <span>{{ $data['total'] }}</span>
                <small>{{ __('reports.ops.total_events') }}</small>
            </div>
            @if ($data['last_activity_at'])
                <div>
                    <span class="ops-hero-meta-sm">{{ $data['last_activity_at'] }}</span>
                    <small>{{ __('reports.ops.last_activity') }}</small>
                </div>
            @endif
        </div>
    </div>

    <div class="card card-elevated">
        <div class="card-header"><h3 class="card-title">{{ __('reports.filters_title') }}</h3></div>
        <div class="card-body">
            <form method="GET" class="reports-filters form-grid ops-filters">
                <div class="form-group">
                    <x-input-label for="date_from" :value="__('reports.date_from')" />
                    <x-text-input id="date_from" name="date_from" type="date" :value="request('date_from')" />
                </div>
                <div class="form-group">
                    <x-input-label for="date_to" :value="__('reports.date_to')" />
                    <x-text-input id="date_to" name="date_to" type="date" :value="request('date_to')" />
                </div>
                <div class="form-group">
                    <x-input-label for="department_id" :value="__('common.org_unit')" />
                    @include('settings.partials.org-unit-select', [
                        'orgUnits' => $orgUnits,
                        'selected' => request('department_id'),
                        'placeholder' => __('common.all_dash'),
                        'showHint' => false,
                    ])
                </div>
                <div class="form-group">
                    <x-input-label for="folder_id" :value="__('reports.filter_folder')" />
                    <select id="folder_id" name="folder_id" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($filterFolders as $folder)
                            <option value="{{ $folder->id }}" @selected(request('folder_id') == $folder->id)>
                                {{ $folder->name }}@if($folder->department) — {{ $folder->department->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="user_id" :value="__('reports.filter_user')" />
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach ($filterUsers as $filterUser)
                            <option value="{{ $filterUser->id }}" @selected(request('user_id') == $filterUser->id)>
                                {{ $filterUser->name }}@if($filterUser->department) — {{ $filterUser->department->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="transaction_search" :value="__('reports.filter_transaction')" />
                    <x-text-input id="transaction_search" name="transaction_search" type="search" :value="request('transaction_search')" :placeholder="__('reports.filter_transaction_placeholder')" />
                </div>
                <div class="form-group">
                    <x-input-label for="event_type" :value="__('reports.filter_event_type')" />
                    <select id="event_type" name="event_type" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach (['created', 'workflow', 'attachment', 'audit', 'access', 'lending'] as $type)
                            <option value="{{ $type }}" @selected(request('event_type') === $type)>{{ __('reports.event_type.'.$type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <x-input-label for="view_mode" :value="__('reports.filter_view_mode')" />
                    <select id="view_mode" name="view_mode" class="form-select">
                        <option value="timeline" @selected(($filter->viewMode ?? 'timeline') === 'timeline')>{{ __('reports.view_mode.timeline') }}</option>
                        <option value="by_user" @selected(($filter->viewMode ?? '') === 'by_user')>{{ __('reports.view_mode.by_user') }}</option>
                        <option value="by_transaction" @selected(($filter->viewMode ?? '') === 'by_transaction')>{{ __('reports.view_mode.by_transaction') }}</option>
                    </select>
                </div>
                <div class="form-group reports-filters-actions">
                    <x-primary-button>{{ __('reports.apply_filters') }}</x-primary-button>
                    <a href="{{ route('reports.show', $reportType) }}" class="btn btn-secondary">{{ __('common.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    @include('reports.partials.export-bar')

    <div class="stats-grid reports-stats-grid">
        <div class="stat-card stat-card--indigo">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.ops.total_events') }}</span>
                <span class="stat-value">{{ $data['total'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--cyan">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.ops.affected_transactions') }}</span>
                <span class="stat-value">{{ $data['affected_transactions'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--slate">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.active_users') }}</span>
                <span class="stat-value">{{ $data['active_users'] }}</span>
            </div>
        </div>
        <div class="stat-card stat-card--emerald">
            <div class="stat-card-body">
                <span class="stat-label">{{ __('reports.ops.shown_events') }}</span>
                <span class="stat-value">{{ $data['shown'] }}</span>
            </div>
        </div>
    </div>

    @if (($data['by_type'] ?? collect())->isNotEmpty())
        <div class="card card-elevated ops-type-summary">
            <div class="card-header"><h3 class="card-title">{{ __('reports.ops.by_event_type') }}</h3></div>
            <div class="card-body">
                <div class="ops-type-chips">
                    @foreach ($data['by_type'] as $row)
                        <span class="ops-type-chip ops-type-chip--{{ $row['type'] }}">
                            <strong>{{ $row['count'] }}</strong>
                            {{ $row['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if (! empty($data['capped']))
        <div class="ops-limit-note">{{ __('reports.ops.collect_cap_note', ['limit' => \App\Services\Reports\SystemOperationsReportService::COLLECT_CAP]) }}</div>
    @endif

    @if (($data['view_mode'] ?? 'timeline') === 'timeline')
        <div class="card card-elevated">
            <div class="card-header">
                <h3 class="card-title">{{ __('reports.ops.timeline_title') }}</h3>
                <p class="card-subtitle">
                    {{ __('reports.ops.page_summary', [
                        'shown' => $data['shown'],
                        'total' => $data['total'],
                        'page' => optional($data['paginator'])->currentPage() ?? 1,
                        'pages' => max(1, optional($data['paginator'])->lastPage() ?? 1),
                    ]) }}
                </p>
            </div>
            <div class="card-body">
                @include('reports.partials.operations-timeline', ['events' => $data['events']])
            </div>
            @if ($data['paginator'] ?? null)
                <div class="card-footer">{{ $data['paginator']->links() }}</div>
            @endif
        </div>
    @else
        @forelse ($data['grouped'] as $group)
            <div class="card card-elevated ops-group-card">
                <div class="card-header ops-group-header">
                    <div>
                        <h3 class="card-title">
                            @if (! empty($group['url']))
                                <a href="{{ $group['url'] }}">{{ $group['title'] }}</a>
                            @else
                                {{ $group['title'] }}
                            @endif
                        </h3>
                        <p class="card-subtitle">
                            {{ $group['subtitle'] ?? '' }}
                            @if (! empty($group['department']))
                                · {{ $group['department'] }}
                            @endif
                        </p>
                    </div>
                    <span class="reports-count-badge" style="--badge-color: #1e3a5f">{{ $group['count'] }}</span>
                </div>
                <div class="card-body">
                    @include('reports.partials.operations-timeline', ['events' => $group['events']])
                </div>
            </div>
        @empty
            <div class="card card-elevated">
                <div class="card-body">
                    <div class="empty-state"><p>{{ __('reports.ops.no_events') }}</p></div>
                </div>
            </div>
        @endforelse
        @if ($data['paginator'] ?? null)
            <div class="card card-elevated">
                <div class="card-footer">{{ $data['paginator']->links() }}</div>
            </div>
        @endif
    @endif
</x-app-layout>
