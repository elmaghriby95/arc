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
                <div class="profile-hero-avatar-wrap">
                    <x-user-avatar :user="$user" size="hero" class="profile-hero-avatar" />
                    @permission('profile.edit')
                        <label for="avatar-upload" class="profile-avatar-edit" title="{{ __('profile.change_avatar') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 0 1 2-2h.93a2 2 0 0 0 1.664-.89l.812-1.22A2 2 0 0 1 10.07 4h3.86a2 2 0 0 1 1.664.89l.812 1.22A2 2 0 0 0 18.07 7H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
                        </label>
                    @endpermission
                </div>

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

        @permission('profile.edit')
            <form id="avatar-upload-form" method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="visually-hidden">
                @csrf
                <input type="file" id="avatar-upload" name="avatar" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()">
            </form>
        @endpermission

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
                        @permission('profile.delete')
                            <button type="button" class="profile-tab-btn profile-tab-btn--danger" data-tab="danger">{{ __('profile.tab.danger') }}</button>
                        @endpermission
                    </div>

                    <div class="profile-tab-panel is-active" data-panel="info">
                        <div class="card">
                            <div class="card-body">
                                @include('profile.partials.update-profile-information-form')

                                @permission('profile.edit')
                                    @if ($user->avatar_path)
                                        <div class="profile-avatar-actions">
                                            <form method="POST" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('{{ __('profile.remove_avatar_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-secondary btn-sm">{{ __('profile.remove_avatar') }}</button>
                                            </form>
                                        </div>
                                    @endif
                                @endpermission
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

                    @permission('profile.delete')
                        <div class="profile-tab-panel" data-panel="danger">
                            <div class="card card-danger">
                                <div class="card-body">
                                    @include('profile.partials.delete-user-form')
                                </div>
                            </div>
                        </div>
                    @endpermission
                </div>
            </div>

            {{-- Right column: activity log --}}
            <aside class="profile-sidebar">
                <div class="card card-elevated profile-activity-card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">{{ __('profile.activity_log') }}</h3>
                            <p class="card-subtitle">{{ __('profile.activity_log_desc') }}</p>
                        </div>
                    </div>
                    <div class="card-body profile-activity-body">
                        @if ($activities->isEmpty())
                            <div class="profile-activity-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                <p>{{ __('profile.no_activity') }}</p>
                            </div>
                        @else
                            <ul class="profile-activity-timeline">
                                @foreach ($activities as $activity)
                                    <li class="profile-activity-item profile-activity-item--{{ $activity['icon'] }}">
                                        <div class="profile-activity-icon">
                                            @include('profile.partials.activity-icon', ['icon' => $activity['icon']])
                                        </div>
                                        <div class="profile-activity-content">
                                            <div class="profile-activity-header">
                                                <strong>{{ $activity['label'] }}</strong>
                                                <time datetime="{{ $activity['occurred_at']->toIso8601String() }}">
                                                    {{ $activity['occurred_at']->diffForHumans() }}
                                                </time>
                                            </div>
                                            @if ($activity['description'])
                                                <p class="profile-activity-desc">
                                                    @if ($activity['url'])
                                                        <a href="{{ $activity['url'] }}">{{ $activity['description'] }}</a>
                                                    @else
                                                        {{ $activity['description'] }}
                                                    @endif
                                                </p>
                                            @endif
                                            @if ($activity['meta'])
                                                <span class="profile-activity-meta">{{ $activity['meta'] }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
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
