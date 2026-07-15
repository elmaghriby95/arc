<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($activeLanguage->direction ?? 'rtl') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('documents.print_title') }} — {{ $attachment->displayName() }}</title>
    <x-inline-css file="document-preview.css" />
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
        .doc-print-stage {
            position: relative;
            width: 100%;
            min-height: calc(100vh - 56px);
            background: #fff;
        }
        .doc-print-stage img.doc-print-media {
            display: block;
            max-width: 100%;
            height: auto;
            margin: 0 auto;
        }
        .doc-print-stage .doc-pdf-viewer {
            padding: 0.5rem;
        }
        @media print {
            .doc-print-toolbar { display: none !important; }
            .doc-print-stage { min-height: auto; }
            .doc-wm-overlay { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="doc-print-toolbar">
        <button type="button" onclick="window.print()">{{ __('documents.print_now') }}</button>
        <button type="button" onclick="window.close()">{{ __('common.close') }}</button>
    </div>

    @php
        $wmContext = $printWatermark['context'] ?? null;
    @endphp

    <div class="doc-print-stage">
        @if ($wmContext)
            <x-document-watermark-overlay :context="$wmContext" />
        @endif

        @if ($isPdf ?? false)
            <div
                class="doc-pdf-viewer"
                data-doc-pdf-viewer
                data-pdf-url="{{ $printUrl }}"
                data-pdf-worker="{{ route('assets.pdfjs', ['file' => 'pdf.worker.min.js']) }}"
                data-pdf-error="{{ __('documents.preview_unavailable') }}"
                @if ($wmContext)
                    data-watermark="{{ e(json_encode($wmContext, JSON_UNESCAPED_UNICODE)) }}"
                @endif
            >
                <p class="doc-pdf-status" data-doc-pdf-status hidden></p>
                <div class="doc-pdf-pages" data-doc-pdf-pages></div>
            </div>
        @else
            <img class="doc-print-media" src="{{ $printUrl }}" alt="{{ $attachment->displayName() }}">
        @endif
    </div>

    @if ($isPdf ?? false)
        <script src="{{ route('assets.pdfjs', ['file' => 'pdf.min.js']) }}"></script>
        <x-inline-js file="document-pdf-preview.js" />
    @endif
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 900);
        });
    </script>
</body>
</html>
