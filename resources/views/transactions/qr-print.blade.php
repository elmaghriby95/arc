<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('transactions.qr_code_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 2rem;
            font-family: Cairo, sans-serif;
            text-align: center;
            color: #0f172a;
        }
        h1 {
            margin: 0 0 1.25rem;
            font-size: 1.2rem;
            font-weight: 800;
        }
        .txn-qr-print-wrap {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
        }
        .txn-qr-print-svg {
            display: block;
            width: {{ $printSize }}px;
            height: {{ $printSize }}px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            padding: 0.5rem;
        }
        .txn-qr-print-svg svg {
            display: block;
            width: 100%;
            height: 100%;
        }
        p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        code {
            display: block;
            font-size: 0.85rem;
            word-break: break-all;
            color: #334155;
            background: #f1f5f9;
            padding: 0.35rem 0.55rem;
            border-radius: 0.4rem;
        }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="txn-qr-print-wrap">
        <h1>{{ __('transactions.qr_code_title') }}</h1>
        <div class="txn-qr-print-svg">{!! $qrSvg !!}</div>
        <p>{{ __('transactions.qr_code_hint') }}</p>
        <code>{{ $qrPayload }}</code>
    </div>
    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
