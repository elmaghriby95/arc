<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        @if ($systemSettings->idleTimeoutEnabled())
            <meta name="idle-timeout-minutes" content="{{ $systemSettings->idleTimeoutMinutes() }}">
            <meta name="logout-url" content="{{ route('logout') }}">
            <meta name="login-url" content="{{ route('login') }}">
            <meta name="session-keepalive-url" content="{{ route('session.keepalive') }}">
        @endif
    @endauth
    <title>{{ $systemSettings->appName() }}</title>
    @if ($systemSettings->hasFavicon())
        <link rel="icon" href="{{ $systemSettings->faviconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <x-inline-css file="navbar.css" />
    <x-inline-css file="notifications.css" />
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        @include('layouts.partials.navbar')

        <main class="app-content">
            @isset($header)
                <div class="page-header-bar">
                    {{ $header }}
                </div>
            @endisset

            <x-flash-messages />
            {{ $slot }}
        </main>
    </div>

    @auth
        @if ($systemSettings->idleTimeoutEnabled())
            <div id="idle-session-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="idle-session-title" hidden>
                <div class="modal-backdrop"></div>
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="idle-session-title" class="modal-title">{{ __('auth.idle_warning_title') }}</h3>
                        </div>
                        <div class="modal-body">
                            <p>{{ __('auth.idle_warning_body') }}</p>
                            <p class="idle-session-countdown" data-idle-countdown aria-live="polite"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-idle-stay>
                                {{ __('auth.idle_stay_signed_in') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
