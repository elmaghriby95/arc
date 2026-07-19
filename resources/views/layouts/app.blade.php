<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $systemSettings->appName() }}</title>
    @if ($systemSettings->hasFavicon())
        <link rel="icon" href="{{ $systemSettings->faviconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet">
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

    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
