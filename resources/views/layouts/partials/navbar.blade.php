<nav class="navbar" data-navbar>
    <div class="navbar-accent" aria-hidden="true"></div>

    <div class="navbar-inner">
        <div class="navbar-start">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                @if ($systemSettings->hasLogo())
                    <img
                        src="{{ $systemSettings->logoUrl() }}"
                        alt="{{ $systemSettings->appName() }}"
                        class="navbar-brand-logo-img"
                    >
                @else
                    <span class="navbar-brand-icon" aria-hidden="true">
                        @include('layouts.partials.system-brand-icon', ['variant' => 'navbar'])
                    </span>
                @endif
                <span class="navbar-brand-copy">
                    <span class="navbar-brand-text">{{ $systemSettings->appName() }}</span>
                    <span class="navbar-brand-tagline">{{ __('nav.tagline') }}</span>
                </span>
            </a>

            <div class="navbar-menu" data-navbar-menu>
                @include('layouts.partials.navbar-menu')
            </div>
        </div>

        <div class="navbar-end">
            @if (($navbarLanguages ?? collect())->isNotEmpty())
            <div class="dropdown navbar-language" data-dropdown>
                <button type="button" class="navbar-language-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('nav.language') }}">
                    <span class="navbar-language-code">{{ strtoupper($activeLanguage->code ?? app()->getLocale()) }}</span>
                    <span class="navbar-user-chevron" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </span>
                </button>
                <div class="dropdown-menu navbar-dropdown navbar-language-menu">
                    @foreach ($navbarLanguages as $lang)
                        <form method="POST" action="{{ route('locale.switch', $lang) }}">
                            @csrf
                            <button type="submit" class="dropdown-item navbar-dropdown-item {{ ($activeLanguage->id ?? null) === $lang->id ? 'is-active' : '' }}">
                                <span class="navbar-language-option-code">{{ strtoupper($lang->code) }}</span>
                                <span>{{ $lang->native_name }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="dropdown navbar-notifications" data-dropdown>
                <button type="button" class="navbar-notifications-btn {{ ($navbarUnreadCount ?? 0) > 0 ? 'has-unread' : '' }}" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-label="{{ __('nav.notifications') }}{{ ($navbarUnreadCount ?? 0) > 0 ? ' — '.__('nav.unread_count', ['count' => $navbarUnreadCount]) : '' }}">
                    <span class="navbar-notifications-btn-inner" aria-hidden="true">
                        <svg class="navbar-notifications-bell" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M9.001 19h6" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                            <path d="M18 8.5V9a6 6 0 1 1-12 0v-.5c0-1.2.5-2.35 1.35-3.15A4.5 4.5 0 0 1 12 3c1.2 0 2.35.48 3.15 1.35.85.8 1.35 1.95 1.35 3.15Z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>
                            <path class="navbar-notifications-bell-clapper" d="M5.5 15.5h13" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        </svg>
                    </span>
                    @if (($navbarUnreadCount ?? 0) > 0)
                        <span class="navbar-notifications-badge" aria-hidden="true">{{ $navbarUnreadCount > 99 ? '99+' : $navbarUnreadCount }}</span>
                        <span class="navbar-notifications-live-dot" aria-hidden="true"></span>
                    @endif
                </button>
                <div class="dropdown-menu navbar-notifications-menu">
                    <div class="navbar-notifications-header">
                        <div class="navbar-notifications-header-start">
                            <span class="navbar-notifications-header-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                                </svg>
                            </span>
                            <div class="navbar-notifications-header-copy">
                                <strong>{{ __('nav.notifications') }}</strong>
                                @if (($navbarUnreadCount ?? 0) > 0)
                                    <span class="navbar-notifications-header-sub">{{ __('nav.unread_count', ['count' => $navbarUnreadCount]) }}</span>
                                @else
                                    <span class="navbar-notifications-header-sub">{{ __('nav.no_new_notifications') }}</span>
                                @endif
                            </div>
                        </div>
                        @if (($navbarUnreadCount ?? 0) > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="navbar-notifications-mark-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span>{{ __('nav.mark_all_read') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="navbar-notifications-list">
                        @forelse ($navbarNotifications ?? [] as $notification)
                            @php
                                $data = $notification->data;
                                $isUnread = $notification->read_at === null;
                                $action = $data['action'] ?? null;
                                $iconVariant = match (true) {
                                    ($data['type'] ?? null) === 'lending_request_status_changed' && in_array($action, ['review_rejected', 'handover_rejected'], true) => 'reject',
                                    ($data['type'] ?? null) === 'lending_request_status_changed' && in_array($action, ['review_approved', 'handover_confirmed', 'returned'], true) => 'approve',
                                    ($data['type'] ?? null) === 'lending_request_status_changed' && $action === 'requested' => 'submit',
                                    $action === 'create' || ($action === null && empty($data['from_status'])) => 'create',
                                    $action === 'approve' => 'approve',
                                    $action === 'reject' => 'reject',
                                    $action === 'submit' => 'submit',
                                    default => 'update',
                                };
                                $statusColor = $data['to_status_color'] ?? '#6366f1';
                                $changedBy = $data['changed_by'] ?? null;
                            @endphp
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="navbar-notification-item {{ $isUnread ? 'is-unread' : '' }}">
                                @csrf
                                <button type="submit" class="navbar-notification-link">
                                    @if ($isUnread)
                                        <span class="navbar-notification-dot" aria-hidden="true"></span>
                                    @endif

                                    <span class="navbar-notification-icon navbar-notification-icon--{{ $iconVariant }}" aria-hidden="true">
                                        @switch($iconVariant)
                                            @case('create')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                                @break
                                            @case('approve')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                                @break
                                            @case('reject')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                                                @break
                                            @case('submit')
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                                                @break
                                            @default
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                                        @endswitch
                                    </span>

                                    <span class="navbar-notification-body">
                                        <span class="navbar-notification-top">
                                            @if (! empty($data['to_status']))
                                                <span class="navbar-notification-status" style="--status-color: {{ $statusColor }}">
                                                    {{ $data['to_status'] }}
                                                </span>
                                            @endif
                                            <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                                        </span>
                                        <span class="navbar-notification-message">{{ $data['message'] ?? '' }}</span>
                                        <span class="navbar-notification-meta">
                                            @if (! empty($data['reference_number']))
                                                <span class="navbar-notification-ref">{{ $data['reference_number'] }}</span>
                                            @endif
                                            @if ($changedBy)
                                                <span class="navbar-notification-by">
                                                    <span class="navbar-notification-by-avatar">{{ mb_substr($changedBy, 0, 1) }}</span>
                                                    {{ $changedBy }}
                                                </span>
                                            @endif
                                        </span>
                                    </span>

                                    <span class="navbar-notification-chevron" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </button>
                            </form>
                        @empty
                            <div class="navbar-notifications-empty">
                                <span class="navbar-notifications-empty-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                                    </svg>
                                </span>
                                <strong>{{ __('nav.no_notifications') }}</strong>
                                <p>{{ __('nav.notifications_empty_hint') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if (($navbarNotifications ?? collect())->isNotEmpty())
                        <div class="navbar-notifications-footer">
                            <span>{{ __('nav.last_notifications', ['count' => ($navbarNotifications ?? collect())->count()]) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="dropdown navbar-user" data-dropdown>
                <button type="button" class="navbar-user-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
                    <x-user-avatar :user="Auth::user()" size="sm" class="navbar-user-avatar" />
                    <span class="navbar-user-meta">
                        <span class="navbar-user-name">{{ Auth::user()->name }}</span>
                        <span class="navbar-user-role">{{ Auth::user()->role?->name ?? '—' }}</span>
                    </span>
                    <span class="navbar-user-chevron" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </span>
                </button>
                <div class="dropdown-menu navbar-dropdown">
                    <div class="navbar-dropdown-header">
                        <x-user-avatar :user="Auth::user()" size="sm" class="navbar-dropdown-avatar" />
                        <div>
                            <strong>{{ Auth::user()->name }}</strong>
                            <small>{{ Auth::user()->role?->name ?? '—' }}</small>
                        </div>
                    </div>
                    <div class="navbar-dropdown-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item navbar-dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/></svg>
                        {{ __('nav.profile') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item navbar-dropdown-item navbar-dropdown-item--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
                            {{ __('nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>

            <button type="button" class="navbar-toggle" data-navbar-toggle aria-label="{{ __('nav.menu') }}" aria-expanded="false">
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
            </button>
        </div>
    </div>

    <div class="navbar-mobile-backdrop" data-navbar-backdrop aria-hidden="true"></div>
</nav>

<x-inline-js file="navbar.js" />
