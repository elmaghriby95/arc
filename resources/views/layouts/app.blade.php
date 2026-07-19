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
    @php
        $appCssPath = public_path('css/app.css');
        $cairoFontPath = public_path('fonts/cairo/cairo-arabic-400.woff2');
    @endphp
    @if (is_readable($cairoFontPath))
        <style>
            @font-face {
                font-family: 'Cairo';
                font-style: normal;
                font-weight: 400;
                font-display: swap;
                src: url('data:font/woff2;base64,{{ base64_encode(file_get_contents($cairoFontPath)) }}') format('woff2');
            }
        </style>
    @endif
    @if (is_readable($appCssPath))
        <style>{!! file_get_contents($appCssPath) !!}</style>
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
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
