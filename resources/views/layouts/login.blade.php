<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $systemSettings->loginText('login_page_title') }} | {{ $systemSettings->appName() }}</title>
    @if ($systemSettings->hasFavicon())
        <link rel="icon" href="{{ $systemSettings->faviconUrl() }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="login-body">
    <div class="login-page">
        <div class="login-page-backdrop" aria-hidden="true"></div>

        <div class="login-center">
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

                <div class="login-card-brand">
                    @if ($systemSettings->hasLogo())
                        <img
                            src="{{ $systemSettings->logoUrl() }}"
                            alt=""
                            class="login-brand-logo-img login-brand-logo-img--centered"
                            style="{{ $systemSettings->loginLogoStyle() }}"
                            decoding="async"
                        >
                    @else
                        <div class="login-brand-logo login-brand-logo--centered">
                            @include('layouts.partials.system-brand-icon', ['variant' => 'login'])
                        </div>
                    @endif

                    @if (filled($systemSettings->loginText('brand_subtitle')))
                        <p class="login-card-subtitle">{{ $systemSettings->loginText('brand_subtitle') }}</p>
                    @endif
                </div>

                {{ $slot }}

                @if (filled($systemSettings->loginText('feature_1')) || filled($systemSettings->loginText('feature_2')) || filled($systemSettings->loginText('feature_3')))
                <ul class="login-features login-features--card">
                    @foreach (['feature_1', 'feature_2', 'feature_3'] as $featureKey)
                        @if (filled($systemSettings->loginText($featureKey)))
                        <li>
                            <span class="login-feature-icon">✓</span>
                            <span>{{ $systemSettings->loginText($featureKey) }}</span>
                        </li>
                        @endif
                    @endforeach
                </ul>
                @endif
            </div>

            <footer class="login-page-footer">
                <span>{{ $systemSettings->loginText('copyright') }}</span>
            </footer>
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
