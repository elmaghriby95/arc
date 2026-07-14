<?php

namespace App\Services\Reports;

use App\Enums\WorkflowAction;
use App\Models\AuditLog;
use App\Models\DocumentAccessAudit;
use App\Models\LendingRequestHistory;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Support\Reports\ReportFilter;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SystemOperationsReportService
{
    public const DISPLAY_LIMIT = 500;

    public const EXPORT_LIMIT = 2000;

    public function generate(ReportFilter $filter, User $user, int $limit = self::DISPLAY_LIMIT): array
    {
        $scope = new ReportScopeService($user);
        $events = $this->collectEvents($filter, $scope, $limit * 2)
            ->sortByDesc(fn (array $event) => $event['occurred_at_ts'])
            ->values();

        $totalMatched = $events->count();
        $events = $events->take($limit)->values();

        $byType = $events->groupBy('event_type')
            ->map(fn (Collection $group, string $type) => [
                'type' => $type,
                'label' => __('reports.event_type.'.$type),
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $actorIds = $events->pluck('actor_id')->filter()->unique();
        $transactionKeys = $events->pluck('transaction_id')->filter()->unique();

        $lastActivity = $events->first();

        $payload = [
            'total' => $totalMatched,
            'shown' => $events->count(),
            'truncated' => $totalMatched > $limit,
            'limit' => $limit,
            'affected_transactions' => $transactionKeys->count(),
            'active_users' => $actorIds->count(),
            'last_activity_at' => $lastActivity['occurred_at'] ?? null,
            'by_type' => $byType,
            'view_mode' => $filter->viewMode,
            'events' => $events,
            'grouped' => collect(),
        ];

        if ($filter->viewMode === 'by_user') {
            $payload['grouped'] = $this->groupByUser($events);
        } elseif ($filter->viewMode === 'by_transaction') {
            $payload['grouped'] = $this->groupByTransaction($events);
        }

        return $payload;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function collectEvents(ReportFilter $filter, ReportScopeService $scope, int $perSource): Collection
    {
        $events = collect();
        $wants = fn (string $type) => $filter->eventType === null || $filter->eventType === $type;

        $transactionQuery = $scope->applySystemOperationsTransactionFilters(
            $scope->transactionsQuery(),
            $filter
        );

        $hasTxnConstraints = $filter->departmentId
            || $filter->folderId
            || $filter->transactionSearch
            || $filter->transactionTypeId
            || $filter->transactionStatusId
            || $scope->scopedDepartmentIds() !== null;

        $transactionIds = null;
        if ($hasTxnConstraints) {
            $transactionIds = (clone $transactionQuery)->pluck('id');
        }

        if ($wants('created')) {
            $events = $events->merge($this->createdEvents($filter, $transactionQuery, $perSource));
        }

        if ($wants('workflow') && Schema::hasTable('transaction_status_histories')
            && ($transactionIds === null || $transactionIds->isNotEmpty())) {
            $events = $events->merge($this->workflowEvents($filter, $transactionIds, $perSource));
        }

        if ($wants('attachment') && Schema::hasTable('transaction_attachments')
            && ($transactionIds === null || $transactionIds->isNotEmpty())) {
            $events = $events->merge($this->attachmentEvents($filter, $transactionIds, $perSource));
        }

        if ($wants('access') && Schema::hasTable('document_access_audits')
            && ($transactionIds === null || $transactionIds->isNotEmpty())) {
            $events = $events->merge($this->accessEvents($filter, $transactionIds, $perSource));
        }

        if ($wants('lending') && Schema::hasTable('lending_request_histories')
            && ($transactionIds === null || $transactionIds->isNotEmpty())) {
            $events = $events->merge($this->lendingEvents($filter, $transactionIds, $perSource));
        }

        if ($wants('audit') && Schema::hasTable('audit_logs')
            && ! $filter->folderId && ! $filter->transactionSearch && ! $filter->transactionTypeId && ! $filter->transactionStatusId) {
            $events = $events->merge($this->auditEvents($filter, $scope, $perSource));
        }

        return $events;
    }

    /** @param \Illuminate\Database\Eloquent\Builder<Transaction> $transactionQuery */
    private function createdEvents(ReportFilter $filter, $transactionQuery, int $limit): Collection
    {
        $query = (clone $transactionQuery)
            ->with(['creator:id,name', 'department:id,name', 'folder:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($filter->userId) {
            $query->where('created_by', $filter->userId);
        }
        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(fn (Transaction $tx) => $this->event(
            type: 'created',
            label: __('reports.ops.transaction_created'),
            occurredAt: $tx->created_at,
            actorId: $tx->created_by,
            actor: $tx->creator?->name,
            department: $tx->department?->name,
            folder: $tx->folder?->name,
            transactionId: $tx->id,
            transactionRef: $tx->reference_number,
            transactionTitle: $tx->title,
            details: $tx->title,
            url: route('transactions.show', $tx),
        ));
    }

    /** @param Collection<int, int>|null $transactionIds */
    private function workflowEvents(ReportFilter $filter, ?Collection $transactionIds, int $limit): Collection
    {
        $query = TransactionStatusHistory::query()
            ->with([
                'transaction:id,reference_number,title,department_id,folder_id',
                'transaction.department:id,name',
                'transaction.folder:id,name',
                'fromStatus:id,name,color',
                'toStatus:id,name,color',
                'changedBy:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($transactionIds !== null) {
            $query->whereIn('transaction_id', $transactionIds);
        }
        if ($filter->userId) {
            $query->where('changed_by', $filter->userId);
        }
        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(function (TransactionStatusHistory $history) {
            $actionLabel = WorkflowAction::tryFrom((string) $history->action)?->label()
                ?? ($history->action ?: '—');
            $from = $history->fromStatus?->name ?? '—';
            $to = $history->toStatus?->name ?? '—';

            return $this->event(
                type: 'workflow',
                label: __('reports.ops.status_changed'),
                occurredAt: $history->created_at,
                actorId: $history->changed_by,
                actor: $history->changedBy?->name,
                department: $history->transaction?->department?->name,
                folder: $history->transaction?->folder?->name,
                transactionId: $history->transaction_id,
                transactionRef: $history->transaction?->reference_number,
                transactionTitle: $history->transaction?->title,
                details: $from.' → '.$to.' ('.$actionLabel.')',
                notes: $history->notes,
                url: $history->transaction ? route('transactions.show', $history->transaction) : null,
                meta: [
                    'from_status' => $from,
                    'to_status' => $to,
                    'to_status_color' => $history->toStatus?->color,
                    'action' => $actionLabel,
                ],
            );
        });
    }

    /** @param Collection<int, int>|null $transactionIds */
    private function attachmentEvents(ReportFilter $filter, ?Collection $transactionIds, int $limit): Collection
    {
        $query = TransactionAttachment::query()
            ->with([
                'transaction:id,reference_number,title,department_id,folder_id',
                'transaction.department:id,name',
                'transaction.folder:id,name',
                'uploader:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($transactionIds !== null) {
            $query->whereIn('transaction_id', $transactionIds);
        }
        if ($filter->userId) {
            $query->where('uploaded_by', $filter->userId);
        }
        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(fn (TransactionAttachment $attachment) => $this->event(
            type: 'attachment',
            label: __('reports.ops.attachment_uploaded'),
            occurredAt: $attachment->created_at,
            actorId: $attachment->uploaded_by,
            actor: $attachment->uploader?->name,
            department: $attachment->transaction?->department?->name,
            folder: $attachment->transaction?->folder?->name,
            transactionId: $attachment->transaction_id,
            transactionRef: $attachment->transaction?->reference_number,
            transactionTitle: $attachment->transaction?->title,
            details: $attachment->displayName(),
            url: $attachment->transaction ? route('transactions.show', $attachment->transaction) : null,
        ));
    }

    /** @param Collection<int, int>|null $transactionIds */
    private function accessEvents(ReportFilter $filter, ?Collection $transactionIds, int $limit): Collection
    {
        $query = DocumentAccessAudit::query()
            ->with([
                'user:id,name',
                'attachment:id,transaction_id,title,original_name,file_name',
                'attachment.transaction:id,reference_number,title,department_id,folder_id',
                'attachment.transaction.department:id,name',
                'attachment.transaction.folder:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($transactionIds !== null) {
            $query->whereHas('attachment', fn ($q) => $q->whereIn('transaction_id', $transactionIds));
        }
        if ($filter->userId) {
            $query->where('user_id', $filter->userId);
        }
        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(function (DocumentAccessAudit $audit) {
            $tx = $audit->attachment?->transaction;
            $actionKey = 'reports.access_action.'.$audit->action_type;
            $actionLabel = __($actionKey);
            if ($actionLabel === $actionKey) {
                $actionLabel = $audit->action_type;
            }

            return $this->event(
                type: 'access',
                label: __('reports.ops.document_access', ['action' => $actionLabel]),
                occurredAt: $audit->created_at,
                actorId: $audit->user_id,
                actor: $audit->user?->name,
                department: $tx?->department?->name,
                folder: $tx?->folder?->name,
                transactionId: $tx?->id,
                transactionRef: $tx?->reference_number,
                transactionTitle: $tx?->title,
                details: $audit->attachment?->displayName() ?? '—',
                notes: $audit->status === 'failure' ? $audit->failure_reason : null,
                url: $tx ? route('transactions.show', $tx) : null,
                meta: [
                    'ip' => $audit->ip_address,
                    'status' => $audit->status,
                ],
            );
        });
    }

    /** @param Collection<int, int>|null $transactionIds */
    private function lendingEvents(ReportFilter $filter, ?Collection $transactionIds, int $limit): Collection
    {
        $query = LendingRequestHistory::query()
            ->with([
                'performer:id,name',
                'lendingRequest:id,transaction_id',
                'lendingRequest.transaction:id,reference_number,title,department_id,folder_id',
                'lendingRequest.transaction.department:id,name',
                'lendingRequest.transaction.folder:id,name',
            ])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($transactionIds !== null) {
            $query->whereHas('lendingRequest', fn ($q) => $q->whereIn('transaction_id', $transactionIds));
        }
        if ($filter->userId) {
            $query->where('performed_by', $filter->userId);
        }
        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(function (LendingRequestHistory $history) {
            $tx = $history->lendingRequest?->transaction;

            return $this->event(
                type: 'lending',
                label: __('reports.ops.lending_action'),
                occurredAt: $history->created_at,
                actorId: $history->performed_by,
                actor: $history->performer?->name,
                department: $tx?->department?->name,
                folder: $tx?->folder?->name,
                transactionId: $tx?->id,
                transactionRef: $tx?->reference_number,
                transactionTitle: $tx?->title,
                details: $history->actionLabel().': '.$history->fromStatusLabel().' → '.($history->toStatusEnum()?->label() ?? $history->to_status),
                notes: $history->notes,
                url: $tx ? route('transactions.show', $tx) : null,
            );
        });
    }

    private function auditEvents(ReportFilter $filter, ReportScopeService $scope, int $limit): Collection
    {
        $query = AuditLog::query()
            ->with(['user:id,name,department_id', 'user.department:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($filter->userId) {
            $query->where('user_id', $filter->userId);
        }

        if ($filter->departmentId) {
            $query->whereHas('user', fn ($q) => $q->where('department_id', $filter->departmentId));
        } elseif ($ids = $scope->scopedDepartmentIds()) {
            $query->whereHas('user', fn ($q) => $q->whereIn('department_id', $ids));
        }

        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }
        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query->get()->map(function (AuditLog $log) {
            $changeSummary = $this->auditChangeSummary($log);

            return $this->event(
                type: 'audit',
                label: $this->auditLabel($log->action),
                occurredAt: $log->created_at,
                actorId: $log->user_id,
                actor: $log->user?->name,
                department: $log->user?->department?->name,
                folder: null,
                transactionId: null,
                transactionRef: null,
                transactionTitle: null,
                details: $changeSummary,
                notes: $log->ip_address,
                url: null,
                meta: [
                    'action' => $log->action,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                ],
            );
        });
    }

    /** @return array<string, mixed> */
    private function event(
        string $type,
        string $label,
        ?Carbon $occurredAt,
        mixed $actorId,
        ?string $actor,
        ?string $department,
        ?string $folder,
        mixed $transactionId,
        ?string $transactionRef,
        ?string $transactionTitle,
        ?string $details,
        ?string $notes = null,
        ?string $url = null,
        array $meta = [],
    ): array {
        $at = $occurredAt ?? now();

        return [
            'event_type' => $type,
            'label' => $label,
            'occurred_at' => $at->format('Y-m-d H:i:s'),
            'occurred_at_display' => $at->format('Y-m-d H:i'),
            'occurred_at_ts' => $at->timestamp,
            'actor_id' => $actorId,
            'actor' => $actor ?? __('reports.unknown_user'),
            'department' => $department ?? '—',
            'folder' => $folder ?? '—',
            'transaction_id' => $transactionId,
            'transaction_ref' => $transactionRef ?? '—',
            'transaction_title' => $transactionTitle ?? '—',
            'details' => $details ?? '—',
            'notes' => $notes,
            'url' => $url,
            'meta' => $meta,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $events */
    private function groupByUser(Collection $events): Collection
    {
        return $events
            ->groupBy(fn (array $e) => $e['actor_id'] ?? 'unknown')
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'key' => $first['actor_id'],
                    'title' => $first['actor'],
                    'subtitle' => $first['department'],
                    'count' => $group->count(),
                    'events' => $group->values(),
                ];
            })
            ->sortByDesc('count')
            ->values();
    }

    /** @param Collection<int, array<string, mixed>> $events */
    private function groupByTransaction(Collection $events): Collection
    {
        return $events
            ->filter(fn (array $e) => $e['transaction_id'] !== null)
            ->groupBy('transaction_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                $sorted = $group->sortBy('occurred_at_ts')->values();

                return [
                    'key' => $first['transaction_id'],
                    'title' => $first['transaction_ref'],
                    'subtitle' => $first['transaction_title'],
                    'department' => $first['department'],
                    'url' => $first['url'],
                    'count' => $sorted->count(),
                    'events' => $sorted,
                ];
            })
            ->sortByDesc(fn (array $g) => $g['events']->last()['occurred_at_ts'] ?? 0)
            ->values();
    }

    private function auditLabel(string $action): string
    {
        $key = 'reports.ops.audit.'.$action;
        $translated = __($key);

        return $translated !== $key ? $translated : (__('reports.ops.audit_generic').': '.$action);
    }

    private function auditChangeSummary(AuditLog $log): string
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        if ($old === [] && $new === []) {
            return $log->action;
        }

        $keys = collect(array_unique(array_merge(array_keys($old), array_keys($new))))
            ->reject(fn ($key) => in_array($key, ['password', 'remember_token', 'updated_at'], true))
            ->take(6);

        if ($keys->isEmpty()) {
            return $log->action;
        }

        return $keys->map(function ($key) use ($old, $new) {
            $from = $old[$key] ?? '—';
            $to = $new[$key] ?? '—';
            if (is_array($from)) {
                $from = json_encode($from, JSON_UNESCAPED_UNICODE);
            }
            if (is_array($to)) {
                $to = json_encode($to, JSON_UNESCAPED_UNICODE);
            }

            return $key.': '.$from.' → '.$to;
        })->implode(' · ');
    }
}
