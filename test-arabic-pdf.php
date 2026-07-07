<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$processor = app(App\Support\Reports\PdfHtmlProcessor::class);
$registry = app(App\Support\Reports\PdfFontRegistry::class);
$dompdf = app(Dompdf\Dompdf::class);
$family = $registry->familyForPdf($dompdf);

$html = view('reports.pdf.layout', [
    'reportType' => App\Enums\ReportType::DepartmentProductivity,
    'filterSummary' => ['الفترة: الكل', 'القسم: إدارة الموارد البشرية'],
    'generatedAt' => '2026-07-07 21:00',
    'generatedBy' => 'أحمد محمد',
    'pdfFontFamily' => $family,
])->render();

$html = $processor->process($html);

$html .= view('reports.pdf.department-productivity', [
    'data' => [
        'totals' => ['departments' => 3, 'created' => 19, 'archived' => 4, 'in_progress' => 0],
        'details' => collect([
            ['department' => 'إدارة الموارد البشرية', 'created' => 5, 'draft' => 1, 'in_progress' => 2, 'archived' => 1, 'completed_in_period' => 1, 'completion_rate' => 20],
        ]),
    ],
])->render();

$pdf = app(Barryvdh\DomPDF\PDF::class);
$pdf->setOption('default_font', $family)->loadHTML($processor->process($html));
file_put_contents(storage_path('app/test-arabic-report.pdf'), $pdf->output());

$sample = (new ArPHP\I18N\Arabic)->utf8Glyphs('التفاصيل');
echo "Original: التفاصيل\n";
echo "Glyphs: $sample\n";
echo "Saved PDF\n";
