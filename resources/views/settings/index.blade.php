<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h2 class="page-title">{{ __('settings.title') }}</h2>
                <p class="page-subtitle">{{ __('settings.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="container">
        <x-flash-messages />

        <div class="settings-hero">
            <div class="settings-hero-content">
                <div class="settings-hero-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="settings-hero-title">{{ __('settings.hero_title') }}</h3>
                    <p class="settings-hero-desc">{{ __('settings.hero_desc') }}</p>
                </div>
            </div>
            <div class="settings-hero-stats">
                <div class="settings-hero-stat">
                    <span class="settings-hero-stat-value">{{ $stats['users'] }}</span>
                    <span class="settings-hero-stat-label">{{ __('settings.users_count') }}</span>
                </div>
                <div class="settings-hero-stat">
                    <span class="settings-hero-stat-value">{{ $stats['departments'] }}</span>
                    <span class="settings-hero-stat-label">{{ __('settings.departments_count') }}</span>
                </div>
                <div class="settings-hero-stat">
                    <span class="settings-hero-stat-value">{{ $stats['roles'] }}</span>
                    <span class="settings-hero-stat-label">{{ __('settings.roles_count') }}</span>
                </div>
            </div>
        </div>

        <h3 class="settings-section-title">{{ __('settings.system_management') }}</h3>

        <div class="settings-modules">
            @permission('settings.users.view')
            <a href="{{ route('settings.users.index') }}" class="settings-module settings-module--users">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.users_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.users_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.users_badge', ['count' => $stats['users']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.roles.view')
            <a href="{{ route('settings.roles.index') }}" class="settings-module settings-module--roles">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.roles_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.roles_desc') }}</p>
                    <div class="settings-module-meta">
                        @foreach ($roleCounts as $role)
                            <span class="settings-badge settings-badge--muted" title="{{ $role->name }}">{{ $role->users_count }}</span>
                        @endforeach
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.organization.view')
            <a href="{{ route('settings.organization.index') }}" class="settings-module settings-module--org">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.org_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.org_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.org_badge', ['count' => $stats['departments']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission
        </div>

        <h3 class="settings-section-title">{{ __('settings.archive_settings') }}</h3>

        <div class="settings-modules">
            @permission('settings.transaction-statuses.view')
            <a href="{{ route('settings.transaction-statuses.index') }}" class="settings-module settings-module--txn-statuses">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.txn_statuses_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.txn_statuses_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.txn_statuses_badge', ['count' => $stats['transactionStatuses']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.document-types.view')
            <a href="{{ route('settings.document-types.index') }}" class="settings-module settings-module--doctypes">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.doc_types_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.doc_types_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.doc_types_badge', ['count' => $stats['documentTypes']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.transaction-types.view')
            <a href="{{ route('settings.transaction-types.index') }}" class="settings-module settings-module--txntypes">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.txn_types_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.txn_types_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.txn_types_badge', ['count' => $stats['transactionTypes']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.folders.view')
            <a href="{{ route('settings.folders.index') }}" class="settings-module settings-module--folders">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.folders_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.folders_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.folders_badge', ['count' => $stats['folders']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.languages.view')
            <a href="{{ route('settings.languages.index') }}" class="settings-module settings-module--languages">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.languages_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.languages_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.languages_badge', ['count' => $stats['languages']]) }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission

            @permission('settings.reference-numbers.view')
            <a href="{{ route('settings.reference-numbers.index') }}" class="settings-module settings-module--ref-numbers">
                <div class="settings-module-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                    </svg>
                </div>
                <div class="settings-module-body">
                    <h3 class="settings-module-title">{{ __('settings.ref_numbers_title') }}</h3>
                    <p class="settings-module-desc">{{ __('settings.ref_numbers_desc') }}</p>
                    <div class="settings-module-meta">
                        <span class="settings-badge">{{ __('settings.ref_numbers_badge') }}</span>
                    </div>
                </div>
                <span class="settings-module-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </span>
            </a>
            @endpermission
        </div>

        @if ($recentUsers->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('settings.recent_users') }}</h3>
                    <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">{{ __('common.view_all') }}</a>
                </div>
                <div class="card-body">
                    <div class="table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('common.name') }}</th>
                                    <th>{{ __('common.email') }}</th>
                                    <th>{{ __('common.role') }}</th>
                                    <th>{{ __('common.department') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <span class="user-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                                                {{ $user->name }}
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="role-pill role-pill--{{ $user->role?->slug ?? 'user' }}">{{ $user->role?->name ?? '—' }}</span></td>
                                        <td>{{ $user->department?->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
