<?php

namespace App\Services\Reports;

use App\Models\Department;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionStatus;
use App\Models\TransactionStatusHistory;
use App\Models\TransactionType;
use App\Models\User;
use App\Support\Reports\ReportFilter;
use Illuminate\Database\Eloquent\Builder;

class ReportScopeService
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return Builder<Transaction> */
    public function transactionsQuery(): Builder
    {
        $query = Transaction::query();

        if ($ids = $this->user->transactionOrgScopeDepartmentIds()) {
            $query->whereIn('department_id', $ids);
        }

        return $query;
    }

    /** @return list<int>|null */
    public function scopedDepartmentIds(): ?array
    {
        return $this->user->transactionOrgScopeDepartmentIds();
    }

    /** @return Builder<TransactionAttachment> */
    public function attachmentsQuery(): Builder
    {
        return TransactionAttachment::query()->whereHas('transaction', function (Builder $query) {
            if ($ids = $this->scopedDepartmentIds()) {
                $query->whereIn('department_id', $ids);
            }
        });
    }

    /** @return Builder<TransactionStatusHistory> */
    public function statusHistoryQuery(): Builder
    {
        return TransactionStatusHistory::query()->whereHas('transaction', function (Builder $query) {
            if ($ids = $this->scopedDepartmentIds()) {
                $query->whereIn('department_id', $ids);
            }
        });
    }

    /** @param Builder<Transaction> $query */
    public function applyTransactionFilters(Builder $query, ReportFilter $filter): Builder
    {
        if ($ids = $this->user->transactionOrgScopeDepartmentIds()) {
            $query->whereIn('department_id', $ids);
        }

        if ($filter->departmentId && $this->user->canAccessDepartment($filter->departmentId)) {
            $query->where('department_id', $filter->departmentId);
        }

        if ($filter->transactionTypeId) {
            $query->where('transaction_type_id', $filter->transactionTypeId);
        }

        if ($filter->transactionStatusId) {
            $query->where('transaction_status_id', $filter->transactionStatusId);
        }

        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query;
    }

    /** @param Builder<TransactionAttachment> $query */
    public function applyAttachmentFilters(Builder $query, ReportFilter $filter): Builder
    {
        return $query->whereHas('transaction', fn (Builder $transactionQuery) => $this->applyTransactionFilters($transactionQuery, $filter));
    }

    /** @param Builder<TransactionStatusHistory> $query */
    public function applyHistoryFilters(Builder $query, ReportFilter $filter): Builder
    {
        $query->whereHas('transaction', fn (Builder $transactionQuery) => $this->applyTransactionFilters($transactionQuery, $filter));

        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query;
    }

    /** @param Builder<Document> $query */
    public function applyDocumentFilters(Builder $query, ReportFilter $filter): Builder
    {
        if ($ids = $this->scopedDepartmentIds()) {
            $query->whereIn('department_id', $ids);
        }

        if ($filter->departmentId && $this->user->canAccessDepartment($filter->departmentId)) {
            $query->where('department_id', $filter->departmentId);
        }

        if ($filter->dateFrom) {
            $query->where('created_at', '>=', $filter->dateFrom);
        }

        if ($filter->dateTo) {
            $query->where('created_at', '<=', $filter->dateTo);
        }

        return $query;
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    public function orgUnitOptions(): array
    {
        $options = Department::optionsForSelect();

        if ($ids = $this->user->transactionOrgScopeDepartmentIds()) {
            $ids = array_map(intval(...), $ids);
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array((int) $option['id'], $ids, true)
            ));
        }

        return $options;
    }

    public function resolveDepartmentLabel(?int $departmentId): ?string
    {
        if (! $departmentId) {
            return null;
        }

        return Department::breadcrumbMap()[$departmentId] ?? null;
    }

    /** @return list<string> */
    public function filterSummary(ReportFilter $filter): array
    {
        $lines = [];

        if ($filter->dateFrom || $filter->dateTo) {
            $from = $filter->dateFrom?->format('Y-m-d') ?? '—';
            $to = $filter->dateTo?->format('Y-m-d') ?? '—';
            $lines[] = __('reports.scope.period', ['from' => $from, 'to' => $to]);
        } else {
            $lines[] = __('reports.scope.period_all');
        }

        if ($filter->departmentId) {
            $lines[] = __('reports.scope.department', [
                'name' => $this->resolveDepartmentLabel($filter->departmentId) ?? '#'.$filter->departmentId,
            ]);
        }

        if ($filter->transactionTypeId) {
            $type = TransactionType::find($filter->transactionTypeId);
            $lines[] = __('reports.scope.transaction_type', [
                'name' => $type?->name ?? '#'.$filter->transactionTypeId,
            ]);
        }

        if ($filter->transactionStatusId) {
            $status = TransactionStatus::find($filter->transactionStatusId);
            $lines[] = __('reports.scope.status', [
                'name' => $status?->name ?? '#'.$filter->transactionStatusId,
            ]);
        }

        if ($filter->staleDays !== 7) {
            $lines[] = __('reports.scope.stale_days', ['days' => $filter->staleDays]);
        }

        return $lines;
    }
}
