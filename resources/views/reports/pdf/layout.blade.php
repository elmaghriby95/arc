<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $reportType->label() }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        html, body {
            direction: rtl;
            unicode-bidi: embed;
            font-family: '{{ $pdfFontFamily }}', 'dejavu sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.6;
            text-align: right;
        }
        .pdf-header {
            border-bottom: 3px solid #4338ca;
            padding-bottom: 12px;
            margin-bottom: 18px;
            text-align: right;
        }
        .pdf-header h1 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: bold;
            color: #312e81;
        }
        .pdf-header p { margin: 0; color: #64748b; font-size: 10px; }
        .pdf-meta {
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            direction: rtl;
            text-align: right;
        }
        .pdf-meta span { display: inline-block; margin-left: 14px; color: #475569; }
        .pdf-section { margin-bottom: 18px; direction: rtl; }
        .pdf-section h2 {
            margin: 0 0 8px;
            font-size: 13px;
            font-weight: bold;
            color: #4338ca;
            border-right: 4px solid #6366f1;
            padding-right: 8px;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            direction: rtl;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: right;
            direction: rtl;
        }
        th {
            background: #4338ca;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) td { background: #f8fafc; }
        .pdf-stats {
            width: 100%;
            margin-bottom: 16px;
            direction: rtl;
        }
        .pdf-stats td {
            border: none;
            background: #eef2ff;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .pdf-stats .label { display: block; font-size: 9px; color: #64748b; }
        .pdf-stats .value { display: block; font-size: 16px; font-weight: bold; color: #4338ca; }
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            direction: rtl;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 9px;
        }
    </style>
</head>
<body dir="rtl">
    <div class="pdf-header">
        <h1>{{ $reportType->label() }}</h1>
        <p>{{ $systemSettings->appName() }} — {{ __('reports.pdf_subtitle') }}</p>
    </div>

    <div class="pdf-meta">
        <span>{{ __('reports.generated_at') }}: {{ $generatedAt }}</span>
        <span>{{ __('reports.generated_by') }}: {{ $generatedBy }}</span>
        @foreach ($filterSummary as $line)
            <span>{{ $line }}</span>
        @endforeach
    </div>

    @yield('content')

    <div class="pdf-footer">
        {{ $systemSettings->appName() }} — {{ $reportType->label() }} — {{ $generatedAt }}
    </div>
</body>
</html>
