<?php

namespace App\Services\Reports;

use App\Models\Document;
use App\Models\TransactionAttachment;
use App\Support\Reports\ReportFilter;

class ConfidentialDocumentsReportService
{
    public function __construct(
        private readonly ReportScopeService $scope,
    ) {}

    /** @return array<string, mixed> */
    public function generate(ReportFilter $filter): array
    {
        $documentsQuery = $this->scope->applyDocumentFilters(
            Document::query()->where('is_confidential', true),
            $filter
        );

        $directCount = (clone $documentsQuery)->count();

        $attachmentQuery = $this->scope->applyAttachmentFilters(
            TransactionAttachment::query()->whereHas('document', fn ($query) => $query->where('is_confidential', true)),
            $filter
        );

        $linkedCount = (clone $attachmentQuery)->count();

        $byDepartmentDocs = (clone $documentsQuery)
            ->join('departments', 'departments.id', '=', 'documents.department_id')
            ->selectRaw('departments.name as department_name, COUNT(*) as total')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department_name,
                'documents' => (int) $row->total,
            ]);

        $byDepartmentAttachments = (clone $attachmentQuery)
            ->join('transactions', 'transactions.id', '=', 'transaction_attachments.transaction_id')
            ->join('departments', 'departments.id', '=', 'transactions.department_id')
            ->selectRaw('departments.name as department_name, COUNT(*) as total')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'department' => $row->department_name,
                'attachments' => (int) $row->total,
            ]);

        $documentDetails = (clone $documentsQuery)
            ->with(['department', 'uploader'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($document) => [
                'source' => __('reports.source_document'),
                'title' => $document->title,
                'reference_number' => $document->reference_number ?? '—',
                'department' => $document->department?->name ?? '—',
                'uploader' => $document->uploader?->name ?? '—',
                'date' => $document->document_date?->format('Y-m-d') ?? $document->created_at->format('Y-m-d'),
            ]);

        $attachmentDetails = (clone $attachmentQuery)
            ->with(['document', 'transaction.department', 'uploader'])
            ->orderByDesc('transaction_attachments.created_at')
            ->get()
            ->map(fn ($attachment) => [
                'source' => __('reports.source_attachment'),
                'title' => $attachment->displayName(),
                'reference_number' => $attachment->reference_number ?? $attachment->transaction?->reference_number ?? '—',
                'department' => $attachment->transaction?->department?->name ?? '—',
                'uploader' => $attachment->uploader?->name ?? '—',
                'date' => $attachment->created_at->format('Y-m-d'),
            ]);

        $details = $documentDetails->concat($attachmentDetails)->sortByDesc('date')->values();

        return [
            'total_documents' => $directCount,
            'total_linked_attachments' => $linkedCount,
            'total' => $directCount + $linkedCount,
            'by_department_documents' => $byDepartmentDocs,
            'by_department_attachments' => $byDepartmentAttachments,
            'details' => $details,
        ];
    }
}
