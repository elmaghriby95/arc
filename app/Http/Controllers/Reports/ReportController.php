<?php

namespace App\Http\Controllers\Reports;

use App\Enums\ReportType;
use App\Exports\ReportWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Services\Reports\ReportRunner;
use App\Support\Reports\ReportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportRunner $runner,
    ) {}

    public function index(): View
    {
        return view('reports.index', [
            'reports' => ReportType::cases(),
        ]);
    }

    public function show(Request $request, string $type): View
    {
        $reportType = $this->resolveType($type);
        $user = $request->user();
        $filter = ReportFilter::fromRequest($request);
        $scope = $this->runner->scope($user);
        $data = $this->runner->run($reportType, $user, $filter);

        return view($reportType->view(), [
            'reportType' => $reportType,
            'filter' => $filter,
            'data' => $data,
            'filterSummary' => $scope->filterSummary($filter),
            'orgUnits' => $scope->orgUnitOptions(),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
            'statuses' => TransactionStatus::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function exportPdf(Request $request, string $type): Response
    {
        $reportType = $this->resolveType($type);
        $user = $request->user();
        $filter = ReportFilter::fromRequest($request);
        $scope = $this->runner->scope($user);
        $data = $this->runner->run($reportType, $user, $filter);

        $pdf = Pdf::loadView($reportType->pdfView(), [
            'reportType' => $reportType,
            'filter' => $filter,
            'data' => $data,
            'filterSummary' => $scope->filterSummary($filter),
            'generatedAt' => now()->format('Y-m-d H:i'),
            'generatedBy' => $user->name,
        ])->setPaper('a4', 'landscape');

        $filename = $reportType->value.'-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request, string $type): BinaryFileResponse
    {
        $reportType = $this->resolveType($type);
        $user = $request->user();
        $filter = ReportFilter::fromRequest($request);
        $data = $this->runner->run($reportType, $user, $filter);

        $filename = $reportType->value.'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            ReportWorkbookExport::forType($reportType, $data),
            $filename
        );
    }

    private function resolveType(string $type): ReportType
    {
        $reportType = ReportType::tryFromRoute($type);

        if (! $reportType) {
            abort(404);
        }

        return $reportType;
    }
}
