<nav class="navbar">
    <div class="navbar-inner">
        <div style="display:flex; align-items:center; gap:1.5rem;">
            <a href="{{ route('dashboard') }}" class="navbar-brand">{{ config('app.name') }}</a>

            <div class="navbar-menu">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">{{ __('nav.dashboard') }}</a>
                @permission('documents.view')
                    <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">{{ __('nav.documents') }}</a>
                @endpermission
                @permission('departments.view')
                    <a href="{{ route('departments.index') }}" class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">{{ __('nav.departments') }}</a>
                @endpermission
                @permission('settings.view')
                    <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">{{ __('nav.settings') }}</a>
                @endpermission
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem;">
            <div class="dropdown navbar-user-desktop" data-dropdown>
                <button type="button" class="dropdown-toggle" data-dropdown-toggle>
                    {{ Auth::user()->name }} ▾
                </button>
                <div class="dropdown-menu">
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">{{ __('nav.profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">{{ __('nav.logout') }}</button>
                    </form>
                </div>
            </div>

            <button type="button" class="navbar-toggle" data-mobile-toggle>☰</button>
        </div>
    </div>

    <div class="mobile-menu" data-mobile-menu>
        <a href="{{ route('dashboard') }}" class="mobile-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">{{ __('nav.dashboard') }}</a>
        @permission('documents.view')
            <a href="{{ route('documents.index') }}" class="mobile-nav-link {{ request()->routeIs('documents.*') ? 'active' : '' }}">{{ __('nav.documents') }}</a>
        @endpermission
        @permission('departments.view')
            <a href="{{ route('departments.index') }}" class="mobile-nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">{{ __('nav.departments') }}</a>
        @endpermission
        @permission('settings.view')
            <a href="{{ route('settings.index') }}" class="mobile-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">{{ __('nav.settings') }}</a>
        @endpermission
        <a href="{{ route('profile.edit') }}" class="mobile-nav-link">{{ __('nav.profile') }}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link">{{ __('nav.logout') }}</button>
        </form>
    </div>
</nav>
