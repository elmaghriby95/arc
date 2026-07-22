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
        private readonly SystemOperationsReportService $systemOperations,
    ) {}

    public function scope(User $user): ReportScopeService
    {
        return new ReportScopeService($user);
    }

    /** @return array<string, mixed> */
    public function run(ReportType $type, User $user, ReportFilter $filter, bool $forExport = false): array
    {
        $scope = $this->scope($user);

        return match ($type) {
            ReportType::TransactionPipeline => $this->pipeline->generate($filter, $scope),
            ReportType::StaleTransactions => $this->stale->generate($filter, $scope),
            ReportType::DepartmentProductivity => $this->productivity->generate($filter, $scope),
            ReportType::DocumentsAttachments => $this->documents->generate($filter, $scope),
            ReportType::StatusHistory => $this->history->generate($filter, $scope),
            ReportType::CompletionTime => $this->completionTime->generate($filter, $scope),
            ReportType::UserActivity => $this->userActivity->generate($filter, $scope),
            ReportType::ReferenceNumbers => $this->referenceNumbers->generate($filter, $scope),
            ReportType::FolderDistribution => $this->folderDistribution->generate($filter, $scope),
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
