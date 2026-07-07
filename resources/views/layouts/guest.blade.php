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
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="guest-layout">
        <div class="guest-card">
            <div class="guest-logo">
                <a href="/">{{ $systemSettings->appName() }}</a>
            </div>
            {{ $slot }}
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
