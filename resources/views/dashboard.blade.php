<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('nav.dashboard') }}</h1>
                <p class="page-subtitle">
                    {{ __('dashboard.welcome', ['name' => Auth::user()->name]) }}
                    @if ($orgBreadcrumb)
                        — <span class="org-path org-path--inline">{{ $orgBreadcrumb }}</span>
                    @endif
                </p>
            </div>
            @permission('transactions.create')
                <a href="{{ route('transactions.create') }}" class="btn btn-primary btn-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                    {{ __('dashboard.create_transaction') }}
                </a>
            @endpermission
        </div>
    </x-slot>

    <div class="dashboard-hero">
        <div class="dashboard-hero-content">
            <h2>{{ __('dashboard.hero_title') }}</h2>
            <p>{{ __('dashboard.hero_desc') }}</p>
        </div>
        <div class="dashboard-hero-badge">
            <span>{{ number_format($heroMetric['value']) }}</span>
            <small>{{ $heroMetric['label'] }}</small>
        </div>
    </div>

    <div class="stats-grid stats-grid--compact">
        @forelse ($statCards as $card)
            @if ($card['url'])
                <a href="{{ $card['url'] }}" class="stat-card stat-card--{{ $card['color'] }} stat-card--link">
            @else
                <div class="stat-card stat-card--{{ $card['color'] }}">
            @endif
                <div class="stat-card-icon">
                    @switch($card['icon'])
                        @case('transactions')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h5M6 3h12a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2-3-2V5a2 2 0 0 1 2-2z"/></svg>
                            @break
                        @case('documents')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                            @break
                        @case('review')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 11l2 2 4-4M7 4h10a2 2 0 0 1 2 2v4.5a8 8 0 0 1-7 7.94 8 8 0 0 1-7-7.94V6a2 2 0 0 1 2-2z"/></svg>
                            @break
                        @case('archive')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M6 7v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7M9 11h6M8 3h8l1 4H7l1-4z"/></svg>
                            @break
                        @case('lending')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8 4h5M7 3h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM14 3v5h5"/></svg>
                            @break
                        @case('departments')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/></svg>
                            @break
                        @case('users')
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 0 1 5.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 0 1 9.288 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                            @break
                    @endswitch
                </div>
                <div class="stat-card-body">
                    <span class="stat-label">{{ $card['label'] }}</span>
                    <span class="stat-value">{{ number_format($card['value']) }}</span>
                </div>
            @if ($card['url'])
                </a>
            @else
                </div>
            @endif
        @empty
            <div class="stat-card stat-card--slate">
                <div class="stat-card-body">
                    <span class="stat-label">{{ __('dashboard.no_widgets') }}</span>
                    <span class="stat-value">0</span>
                </div>
            </div>
        @endforelse
    </div>

    <div class="card card-elevated">
        <div class="card-header">
            <div>
                <h3 class="card-title">{{ __('dashboard.recent_documents') }}</h3>
                <p class="card-subtitle">{{ __('dashboard.recent_documents_sub') }}</p>
            </div>
            @if ($canViewDocuments)
                <a href="{{ route('documents.index') }}" class="btn btn-secondary btn-sm">{{ __('common.view_all') }}</a>
            @endif
        </div>
        <div class="card-body card-body-flush">
            @if ($recentAttachments->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                    </div>
                    <p>{{ __('dashboard.no_documents') }}</p>
                    @permission('transactions.create')
                        <a href="{{ route('transactions.create') }}" class="btn btn-primary">{{ __('dashboard.create_transaction') }}</a>
                    @endpermission
                </div>
            @else
                <div class="table-wrapper">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.document') }}</th>
                                <th>{{ __('dashboard.transaction') }}</th>
                                <th>{{ __('common.department') }}</th>
                                <th>{{ __('common.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentAttachments as $attachment)
                                <tr>
                                    <td>
                                        @if ($canOpenDocuments && Auth::user()->canAccessAttachment($attachment))
                                            <a href="{{ route('documents.show', $attachment) }}" class="table-title">{{ $attachment->displayName() }}</a>
                                        @else
                                            <span class="table-title">{{ $attachment->displayName() }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canViewTransactions && Auth::user()->canAccessTransaction($attachment->transaction))
                                            <a href="{{ route('transactions.show', $attachment->transaction) }}" class="ref-pill">{{ $attachment->transaction->reference_number }}</a>
                                        @else
                                            <span class="ref-pill">{{ $attachment->transaction->reference_number }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $attachment->transaction->department?->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $attachment->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
