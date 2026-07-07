@php
    $archiveRoutes = ['transactions.index', 'transactions.show', 'transactions.create', 'transactions.edit', 'transactions.review-log', 'lending-requests.index', 'lending-requests.show', 'lending-requests.log'];
    $archiveActive = request()->routeIs(...$archiveRoutes);

    $hasArchiveMenu = auth()->user()?->hasPermission('transactions.view')
        || auth()->user()?->hasPermission('transactions.review-log.view')
        || auth()->user()?->hasPermission('lending-requests.view');

    $structureActive = request()->routeIs('departments.*', 'categories.*');
    $hasStructureMenu = auth()->user()?->hasPermission('departments.view')
        || auth()->user()?->hasPermission('categories.view');
@endphp

<a href="{{ route('dashboard') }}" class="navbar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
    <span class="navbar-link-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9.5z"/></svg>
    </span>
    <span class="navbar-link-text">{{ __('nav.dashboard') }}</span>
</a>

@permission('documents.view')
    <a href="{{ route('documents.index') }}" class="navbar-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}">
        <span class="navbar-link-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
        </span>
        <span class="navbar-link-text">{{ __('nav.documents') }}</span>
    </a>
@endpermission

@if ($hasArchiveMenu)
    <div class="dropdown navbar-nav-group" data-navbar-dropdown>
        <button type="button" class="navbar-link navbar-link--group {{ $archiveActive ? 'is-active' : '' }}" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
            <span class="navbar-link-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
            </span>
            <span class="navbar-link-text">{{ __('nav.archive') }}</span>
            <span class="navbar-link-chevron" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </span>
        </button>
        <div class="dropdown-menu navbar-nav-submenu" data-group-label="{{ __('nav.archive') }}">
            @permission('transactions.view')
                <a href="{{ route('transactions.index') }}" class="dropdown-item navbar-nav-subitem {{ request()->routeIs('transactions.index', 'transactions.show', 'transactions.create', 'transactions.edit') ? 'is-active' : '' }}">
                    <span class="navbar-nav-subitem-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5"/></svg>
                    </span>
                    <span>{{ __('nav.transactions') }}</span>
                </a>
            @endpermission
            @permission('transactions.review-log.view')
                @if (Route::has('transactions.review-log'))
                    <a href="{{ route('transactions.review-log') }}" class="dropdown-item navbar-nav-subitem {{ request()->routeIs('transactions.review-log') ? 'is-active' : '' }}">
                        <span class="navbar-nav-subitem-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                        </span>
                        <span>{{ __('nav.review_log') }}</span>
                    </a>
                @endif
            @endpermission
            @permission('lending-requests.view')
                @if (Route::has('lending-requests.index'))
                    <a href="{{ route('lending-requests.index') }}" class="dropdown-item navbar-nav-subitem {{ request()->routeIs('lending-requests.*') ? 'is-active' : '' }}">
                        <span class="navbar-nav-subitem-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        </span>
                        <span>{{ __('nav.lending_requests') }}</span>
                    </a>
                @endif
            @endpermission
        </div>
    </div>
@endif

@if (auth()->user()?->canAccessReports())
    <a href="{{ route('reports.index') }}" class="navbar-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
        <span class="navbar-link-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
        </span>
        <span class="navbar-link-text">{{ __('nav.reports') }}</span>
    </a>
@endif

@if ($hasStructureMenu)
    <div class="dropdown navbar-nav-group" data-navbar-dropdown>
        <button type="button" class="navbar-link navbar-link--group {{ $structureActive ? 'is-active' : '' }}" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
            <span class="navbar-link-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
            </span>
            <span class="navbar-link-text">{{ __('nav.structure') }}</span>
            <span class="navbar-link-chevron" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </span>
        </button>
        <div class="dropdown-menu navbar-nav-submenu" data-group-label="{{ __('nav.structure') }}">
            @permission('departments.view')
                <a href="{{ route('departments.index') }}" class="dropdown-item navbar-nav-subitem {{ request()->routeIs('departments.*') ? 'is-active' : '' }}">
                    <span class="navbar-nav-subitem-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                    </span>
                    <span>{{ __('nav.departments') }}</span>
                </a>
            @endpermission
            @permission('categories.view')
                <a href="{{ route('categories.index') }}" class="dropdown-item navbar-nav-subitem {{ request()->routeIs('categories.*') ? 'is-active' : '' }}">
                    <span class="navbar-nav-subitem-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A2 2 0 0 1 3 12V7a4 4 0 0 1 4-4z"/></svg>
                    </span>
                    <span>{{ __('nav.categories') }}</span>
                </a>
            @endpermission
        </div>
    </div>
@endif

@permission('settings.view')
    <a href="{{ route('settings.index') }}" class="navbar-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}">
        <span class="navbar-link-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        </span>
        <span class="navbar-link-text">{{ __('nav.settings') }}</span>
    </a>
@endpermission
