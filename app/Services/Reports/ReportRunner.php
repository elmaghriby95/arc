<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use App\Models\User;
use App\Support\Reports\ReportFilter;

class ReportRunner
{
    public function __construct(
        private readonly TransactionPipelineReportService $pipeline,
        private readonly StaleTransactionsReportService $stale,
        private readonly DepartmentProductivityReportService $productivity,
        private readonly DocumentsAttachmentsReportService $documents,
        private readonly StatusHistoryReportService $history,
    ) {}

    public function scope(User $user): ReportScopeService
    {
        return new ReportScopeService($user);
    }

    /** @return array<string, mixed> */
    public function run(ReportType $type, User $user, ReportFilter $filter): array
    {
        return match ($type) {
            ReportType::TransactionPipeline => $this->pipeline->generate($filter),
            ReportType::StaleTransactions => $this->stale->generate($filter),
            ReportType::DepartmentProductivity => $this->productivity->generate($filter),
            ReportType::DocumentsAttachments => $this->documents->generate($filter),
            ReportType::StatusHistory => $this->history->generate($filter),
        };
    }
}
