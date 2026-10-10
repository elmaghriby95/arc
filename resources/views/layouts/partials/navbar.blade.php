<aside class="app-sidebar" data-navbar>
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <x-arkeon-lockup tone="light" />
    </a>

    <nav class="navbar-menu sidebar-nav" data-navbar-menu>
        @include('layouts.partials.navbar-menu')
    </nav>
</aside>

<div class="navbar-mobile-backdrop" data-navbar-backdrop aria-hidden="true"></div>
