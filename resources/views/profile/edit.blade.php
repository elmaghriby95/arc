<x-app-layout>
    @push('styles')
        <x-inline-css file="profile.css" />
    @endpush

    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('profile.title') }}</h1>
                <p class="page-subtitle">{{ __('profile.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="profile-page">
        {{-- Hero --}}
        <div class="profile-hero">
            <div class="profile-hero-bg"></div>
            <div class="profile-hero-content">
                <div class="profile-hero-info">
                    <div class="profile-hero-name-row">
                        <h2 class="profile-hero-name">{{ $user->name }}</h2>
                        <span class="role-pill role-pill--{{ $user->roleSlug() }}">{{ $user->role?->name ?? '—' }}</span>
                    </div>
                    <p class="profile-hero-email">{{ $user->email }}</p>

                    <div class="profile-hero-meta">
                        @if ($orgBreadcrumb)
                            <span class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"/></svg>
                                <span class="org-path">{{ $orgBreadcrumb }}</span>
                            </span>
                        @endif

                        @if ($user->last_login_at)
                            <span class="profile-meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                {{ __('profile.last_login') }}: {{ $user->last_login_at->diffForHumans() }}
                                @if ($user->last_login_ip)
                                    <span class="profile-meta-ip">({{ $user->last_login_ip }})</span>
                                @endif
                            </span>
                        @else
                            <span class="profile-meta-item profile-meta-item--muted">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                {{ __('profile.no_login_yet') }}
                            </span>
                        @endif

                        <span class="profile-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
                            {{ __('profile.member_since') }}: {{ $user->created_at->translatedFormat('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="profile-stats-grid">
            <div class="profile-stat-card profile-stat-card--indigo">
                <span class="profile-stat-value">{{ $stats['transactions'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.transactions') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--cyan">
                <span class="profile-stat-value">{{ $stats['documents'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.documents') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--violet">
                <span class="profile-stat-value">{{ $stats['workflow_actions'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.workflow') }}</span>
            </div>
            <div class="profile-stat-card profile-stat-card--emerald">
                <span class="profile-stat-value">{{ $stats['audit_entries'] }}</span>
                <span class="profile-stat-label">{{ __('profile.stats.audit') }}</span>
            </div>
        </div>

        <div class="profile-layout">
            {{-- Left column: org info + settings --}}
            <div class="profile-main">
                {{-- Organizational data --}}
                <div class="card card-elevated">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('profile.org_data') }}</h3>
                    </div>
                    <div class="card-body">
                        <dl class="profile-info-grid">
                            <div class="profile-info-item">
                                <dt>{{ __('profile.role') }}</dt>
                                <dd><span class="role-pill role-pill--{{ $user->roleSlug() }}">{{ $user->role?->name ?? '—' }}</span></dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.department') }}</dt>
                                <dd>
                                    @if ($orgBreadcrumb)
                                        <span class="org-path">{{ $orgBreadcrumb }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.language') }}</dt>
                                <dd>{{ $user->language?->name ?? __('profile.default_language') }}</dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.email_status') }}</dt>
                                <dd>
                                    @if ($user->hasVerifiedEmail())
                                        <span class="profile-status-badge profile-status-badge--success">{{ __('profile.email_verified') }}</span>
                                    @else
                                        <span class="profile-status-badge profile-status-badge--warning">{{ __('profile.email_unverified') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.account_created') }}</dt>
                                <dd>{{ $user->created_at->translatedFormat('l, d F Y — H:i') }}</dd>
                            </div>
                            <div class="profile-info-item">
                                <dt>{{ __('profile.last_updated') }}</dt>
                                <dd>{{ $user->updated_at->diffForHumans() }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Profile settings tabs --}}
                <div class="profile-tabs" data-profile-tabs>
                    <div class="profile-tabs-nav">
                        <button type="button" class="profile-tab-btn is-active" data-tab="info">{{ __('profile.tab.info') }}</button>
                        <button type="button" class="profile-tab-btn" data-tab="password">{{ __('profile.tab.password') }}</button>
                        @if (! $user->isAdmin())
                            @permission('profile.delete')
                                <button type="button" class="profile-tab-btn profile-tab-btn--danger" data-tab="danger">{{ __('profile.tab.danger') }}</button>
                            @endpermission
                        @endif
                    </div>

                    <div class="profile-tab-panel is-active" data-panel="info">
                        <div class="card">
                            <div class="card-body">
                                @include('profile.partials.update-profile-information-form')
                            </div>
                        </div>
                    </div>

                    <div class="profile-tab-panel" data-panel="password">
                        <div class="card">
                            <div class="card-body">
                                @include('profile.partials.update-password-form')
                            </div>
                        </div>
                    </div>

                    @if (! $user->isAdmin())
                        @permission('profile.delete')
                            <div class="profile-tab-panel" data-panel="danger">
                                <div class="card card-danger">
                                    <div class="card-body">
                                        @include('profile.partials.delete-user-form')
                                    </div>
                                </div>
                            </div>
                        @endpermission
                    @endif
                </div>
            </div>

            {{-- Right column: activity log --}}
            <aside class="profile-sidebar">
                @include('users.partials.activity-sidebar', ['activities' => $activities])
            </aside>
        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('[data-profile-tabs]').forEach(function (tabs) {
            tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var tab = btn.dataset.tab;
                    tabs.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    tabs.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('is-active', p.dataset.panel === tab); });
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
