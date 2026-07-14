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
        private readonly CompletionTimeReportService $completionTime,
        private readonly UserActivityReportService $userActivity,
        private readonly ReferenceNumbersReportService $referenceNumbers,
        private readonly FolderDistributionReportService $folderDistribution,
        private readonly ConfidentialDocumentsReportService $confidentialDocuments,
        private readonly SystemOperationsReportService $systemOperations,
    ) {}

    public function scope(User $user): ReportScopeService
    {
        return new ReportScopeService($user);
    }

    /** @return array<string, mixed> */
    public function run(ReportType $type, User $user, ReportFilter $filter, bool $forExport = false): array
    {
        return match ($type) {
            ReportType::TransactionPipeline => $this->pipeline->generate($filter),
            ReportType::StaleTransactions => $this->stale->generate($filter),
            ReportType::DepartmentProductivity => $this->productivity->generate($filter),
            ReportType::DocumentsAttachments => $this->documents->generate($filter),
            ReportType::StatusHistory => $this->history->generate($filter),
            ReportType::CompletionTime => $this->completionTime->generate($filter),
            ReportType::UserActivity => $this->userActivity->generate($filter),
            ReportType::ReferenceNumbers => $this->referenceNumbers->generate($filter),
            ReportType::FolderDistribution => $this->folderDistribution->generate($filter),
            ReportType::ConfidentialDocuments => $this->confidentialDocuments->generate($filter),
            ReportType::SystemOperations => $this->systemOperations->generate(
                filter: $filter,
                user: $user,
                page: max(1, (int) request()->integer('page', 1)),
                forExport: $forExport,
                forPdf: false,
            ),
        };
    }

    /** @return array<string, mixed> */
    public function runPdf(ReportType $type, User $user, ReportFilter $filter): array
    {
        if ($type === ReportType::SystemOperations) {
            return $this->systemOperations->generate(
                filter: $filter,
                user: $user,
                page: 1,
                forExport: false,
                forPdf: true,
            );
        }

        return $this->run($type, $user, $filter, forExport: true);
    }
}
