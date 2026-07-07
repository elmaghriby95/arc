<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.login_page_title') }} | {{ $systemSettings->appName() }}</title>
    @if ($systemSettings->hasFavicon())
        <link rel="icon" href="{{ $systemSettings->faviconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
    <div class="login-page">
        <aside class="login-brand">
            <div class="login-brand-content">
                @if ($systemSettings->hasLogo())
                    <img
                        src="{{ $systemSettings->logoUrl() }}"
                        alt="{{ $systemSettings->appName() }}"
                        class="login-brand-logo-img"
                    >
                @else
                    <div class="login-brand-logo">
                        @include('layouts.partials.system-brand-icon', ['variant' => 'login'])
                    </div>
                    <h1 class="login-brand-title">{{ $systemSettings->appName() }}</h1>
                @endif

                <p class="login-brand-subtitle">{{ __('auth.brand_subtitle') }}</p>

                <ul class="login-features">
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>{{ __('auth.feature_1') }}</span>
                    </li>
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>{{ __('auth.feature_2') }}</span>
                    </li>
                    <li>
                        <span class="login-feature-icon">✓</span>
                        <span>{{ __('auth.feature_3') }}</span>
                    </li>
                </ul>
            </div>

            <div class="login-brand-footer">
                <span>© {{ date('Y') }} {{ $systemSettings->appName() }}</span>
            </div>
        </aside>

        <main class="login-main">
            <div class="login-form-wrapper">
                @if (($navbarLanguages ?? collect())->isNotEmpty())
                <div class="login-language-switcher">
                    @foreach ($navbarLanguages as $lang)
                        <form method="POST" action="{{ route('locale.switch', $lang) }}">
                            @csrf
                            <button type="submit" class="login-language-btn {{ ($activeLanguage->id ?? null) === $lang->id ? 'is-active' : '' }}">
                                {{ $lang->native_name }}
                            </button>
                        </form>
                    @endforeach
                </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
