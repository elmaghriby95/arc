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
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Cairo, sans-serif;
            text-align: center;
            color: #0f172a;
        }
        .txn-qr-print-wrap {
            display: flex;
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
        .txn-qr-print-payload {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #0f172a;
        }
        @page {
            margin: 0;
        }
        @media print {
            body {
                min-height: 100vh;
            }
        }
    </style>
</head>
<body>
    <div class="txn-qr-print-wrap">
        <div class="txn-qr-print-svg">{!! $qrSvg !!}</div>
        <p class="txn-qr-print-payload">{{ $qrPayload }}</p>
    </div>
    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
