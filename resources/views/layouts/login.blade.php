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
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800|outfit:600,700,800&display=swap" rel="stylesheet">
    @unless ($systemSettings->hasFavicon())
        <link rel="icon" type="image/svg+xml" href="{{ asset('branding/arkeon-mark.svg') }}">
    @endunless
    <x-inline-css file="login.css" />
</head>
<body class="login-body">
    <div class="login-page">
        <div class="login-page-backdrop" style="background-image: linear-gradient(180deg, rgba(0, 58, 112, 0.18), rgba(0, 40, 72, 0.45)), url('{{ asset('branding/login-campus.svg') }}')" aria-hidden="true"></div>

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
                    <x-arkeon-lockup tone="dark" />
                </div>

                {{ $slot }}
            </div>

            <footer class="login-page-footer">
                <span>{{ $systemSettings->loginText('copyright') }}</span>
            </footer>
        </div>
    </div>

    <x-inline-js file="login.js" />
</body>
</html>
