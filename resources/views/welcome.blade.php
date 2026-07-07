<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="guest-layout">
        <div class="guest-card" style="text-align:center;">
            <h1 class="page-title">{{ config('app.name') }}</h1>
            <p class="text-muted">{{ __('welcome.tagline') }}</p>
            <div class="form-actions" style="justify-content:center; margin-top:1.5rem;">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">{{ __('nav.dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">{{ __('auth.login') }}</a>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>
