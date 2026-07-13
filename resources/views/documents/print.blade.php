<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($activeLanguage->direction ?? 'rtl') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('documents.print_title') }} — {{ $attachment->displayName() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .doc-print-toolbar {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .doc-print-toolbar button {
            font: inherit;
            padding: 0.45rem 0.9rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 0.4rem;
            cursor: pointer;
        }
        .doc-print-media {
            width: 100%;
            min-height: calc(100vh - 56px);
            border: 0;
            display: block;
            margin: 0 auto;
        }
        img.doc-print-media {
            max-width: 100%;
            height: auto;
            min-height: 0;
        }
        @media print {
            .doc-print-toolbar { display: none !important; }
            .doc-print-media { min-height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="doc-print-toolbar">
        <button type="button" onclick="window.print()">{{ __('documents.print_now') }}</button>
        <button type="button" onclick="window.close()">{{ __('common.close') }}</button>
    </div>
    @if ($isPdf ?? false)
        <iframe class="doc-print-media" src="{{ $printUrl }}" title="{{ $attachment->displayName() }}"></iframe>
    @else
        <img class="doc-print-media" src="{{ $printUrl }}" alt="{{ $attachment->displayName() }}">
    @endif
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 600);
        });
    </script>
</body>
</html>
