<nav class="navbar" data-navbar>
    <div class="navbar-accent" aria-hidden="true"></div>

    <div class="navbar-inner">
        <div class="navbar-start">
            <a href="{{ route('dashboard') }}" class="navbar-brand">
                <span class="navbar-brand-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h4"/>
                    </svg>
                </span>
                <span class="navbar-brand-copy">
                    <span class="navbar-brand-text">{{ config('app.name') }}</span>
                    <span class="navbar-brand-tagline">إدارة الوثائق والأرشفة</span>
                </span>
            </a>

            <div class="navbar-menu" data-navbar-menu>
                <a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                    <span class="navbar-link-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z"/></svg>
                    </span>
                    <span class="navbar-link-text">{{ __('Dashboard') }}</span>
                </a>
                @permission('documents.view')
                    <a href="{{ route('documents.index') }}" class="navbar-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}">
                        <span class="navbar-link-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
                        </span>
                        <span class="navbar-link-text">الوثائق</span>
                    </a>
                @endpermission
                @permission('transactions.view')
                    <a href="{{ route('transactions.index') }}" class="navbar-link {{ request()->routeIs('transactions.*') ? 'is-active' : '' }}">
                        <span class="navbar-link-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                        </span>
                        <span class="navbar-link-text">إدارة الأرشفة</span>
                    </a>
                @endpermission
                @permission('departments.view')
                    <a href="{{ route('departments.index') }}" class="navbar-link {{ request()->routeIs('departments.*') ? 'is-active' : '' }}">
                        <span class="navbar-link-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                        </span>
                        <span class="navbar-link-text">الأقسام</span>
                    </a>
                @endpermission
                @permission('categories.view')
                    <a href="{{ route('categories.index') }}" class="navbar-link {{ request()->routeIs('categories.*') ? 'is-active' : '' }}">
                        <span class="navbar-link-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
                        </span>
                        <span class="navbar-link-text">التصنيفات</span>
                    </a>
                @endpermission
                @permission('settings.view')
                    <a href="{{ route('settings.index') }}" class="navbar-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">
                        <span class="navbar-link-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                        </span>
                        <span class="navbar-link-text">الإعدادات</span>
                    </a>
                @endpermission
            </div>
        </div>

        <div class="navbar-end">
            <div class="dropdown navbar-notifications" data-dropdown>
                <button type="button" class="navbar-notifications-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false" aria-label="الإشعارات">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6.002 6.002 0 0 0-4-5.659V5a2 2 0 1 0-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"/>
                    </svg>
                    @if (($navbarUnreadCount ?? 0) > 0)
                        <span class="navbar-notifications-badge">{{ $navbarUnreadCount > 99 ? '99+' : $navbarUnreadCount }}</span>
                    @endif
                </button>
                <div class="dropdown-menu navbar-notifications-menu">
                    <div class="navbar-notifications-header">
                        <strong>الإشعارات</strong>
                        @if (($navbarUnreadCount ?? 0) > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="navbar-notifications-mark-all">تعليم الكل كمقروء</button>
                            </form>
                        @endif
                    </div>
                    <div class="navbar-notifications-list">
                        @forelse ($navbarNotifications ?? [] as $notification)
                            @php
                                $data = $notification->data;
                                $isUnread = $notification->read_at === null;
                            @endphp
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="navbar-notification-item {{ $isUnread ? 'is-unread' : '' }}">
                                @csrf
                                <button type="submit" class="navbar-notification-link">
                                    <span class="navbar-notification-message">{{ $data['message'] ?? '' }}</span>
                                    <span class="navbar-notification-meta">
                                        <span class="navbar-notification-ref">{{ $data['reference_number'] ?? '' }}</span>
                                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                                    </span>
                                </button>
                            </form>
                        @empty
                            <div class="navbar-notifications-empty">لا توجد إشعارات</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="dropdown navbar-user" data-dropdown>
                <button type="button" class="navbar-user-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
                    <span class="navbar-user-avatar">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
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
                        <span class="navbar-dropdown-avatar">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
                        <div>
                            <strong>{{ Auth::user()->name }}</strong>
                            <small>{{ Auth::user()->role?->name ?? '—' }}</small>
                        </div>
                    </div>
                    <div class="navbar-dropdown-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="dropdown-item navbar-dropdown-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/></svg>
                        {{ __('Profile') }}
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item navbar-dropdown-item navbar-dropdown-item--danger">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/></svg>
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>

            <button type="button" class="navbar-toggle" data-navbar-toggle aria-label="القائمة" aria-expanded="false">
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
                <span class="navbar-toggle-bar"></span>
            </button>
        </div>
    </div>

    <div class="navbar-mobile-backdrop" data-navbar-backdrop aria-hidden="true"></div>
</nav>
