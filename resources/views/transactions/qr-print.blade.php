<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $activeLanguage->direction ?? 'rtl' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('transactions.qr_code_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,600,700,800|outfit:700,800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            background: #e2e8f0;
        }
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 16px 0;
            font-family: Cairo, sans-serif;
            text-align: center;
            color: #0f172a;
        }
        .txn-qr-sheet {
            width: 210mm;
            height: 297mm;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }
        .txn-qr-print-wrap {
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            padding: 16mm 12mm 8mm;
        }
        .txn-qr-print-svg {
            display: block;
            width: {{ $printSize }}px;
            height: {{ $printSize }}px;
            max-width: 100%;
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
        .txn-qr-print-logo {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0 12mm 14mm;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .txn-qr-print-logo img,
        .txn-qr-print-logo .arkeon-mark {
            display: block;
            width: auto;
            height: 58px;
            max-width: 180px;
            object-fit: contain;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .txn-qr-print-brand {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.1rem;
            color: #003A70;
        }
        .txn-qr-print-brand-en {
            font-family: Outfit, Cairo, sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            line-height: 1;
        }
        .txn-qr-print-brand-ar {
            font-size: 0.85rem;
            font-weight: 700;
            line-height: 1;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            html, body {
                background: #fff;
                padding: 0;
            }
            .txn-qr-sheet {
                width: 210mm;
                height: 297mm;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="txn-qr-sheet">
        <div class="txn-qr-print-wrap">
            <div class="txn-qr-print-svg">{!! $qrSvg !!}</div>
            <p class="txn-qr-print-payload">{{ $qrPayload }}</p>
        </div>
        <footer class="txn-qr-print-logo">
            @if ($systemSettings->hasLogo())
                <img src="{{ $systemSettings->logoUrl() }}" alt="{{ $systemSettings->appName() }}">
            @else
                <span class="txn-qr-print-mark">
                    @include('layouts.partials.arkeon-mark')
                </span>
                <span class="txn-qr-print-brand">
                    <span class="txn-qr-print-brand-en">ARKÉON</span>
                    <span class="txn-qr-print-brand-ar">أركيون</span>
                </span>
            @endif
        </footer>
    </div>
    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
</body>
</html>
