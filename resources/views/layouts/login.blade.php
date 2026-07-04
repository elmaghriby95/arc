<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
    <div class="login-page">
        <aside class="login-brand">
            <div class="login-brand-content">
                <div class="login-brand-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h4"/>
                    </svg>
                </div>

                <h1 class="login-brand-title">{{ config('app.name') }}</h1>
                <p class="login-brand-subtitle">منصة متكاملة لإدارة وأرشفة الوثائق الإلكترونية بأمان وكفاءة</p>

                <ul class="login-features">
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>أرشفة مركزية للوثائق والمراسلات</span>
                    </li>
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>بحث سريع وتصنيف ذكي</span>
                    </li>
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>تتبع الإصدارات وسجل العمليات</span>
                    </li>
                </ul>
            </div>

            <div class="login-brand-footer">
                <span>© {{ date('Y') }} {{ config('app.name') }}</span>
            </div>
        </aside>

        <main class="login-main">
            <div class="login-form-wrapper">
                {{ $slot }}
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
