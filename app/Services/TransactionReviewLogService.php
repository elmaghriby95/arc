<?php

namespace App\Services;

use App\Enums\WorkflowAction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TransactionReviewLogService
{
    /** @var list<string> */
    private const REVIEW_ACTIONS = [
        WorkflowAction::Approve->value,
        WorkflowAction::Reject->value,
    ];

    public function canViewTeamLog(User $user): bool
    {
        return $user->hasPermission('transactions.view-all');
    }

    /** @param Builder<TransactionStatusHistory> $query */
    public function applyScope(Builder $query, User $user): void
    {
        if ($this->canViewTeamLog($user)) {
            return;
        }

        if ($this->showsPersonalLogOnly($user)) {
            $query->where('changed_by', $user->id);

            return;
        }

        $departmentIds = $user->transactionOrgScopeDepartmentIds();

        $query->where(function (Builder $outer) use ($user, $departmentIds) {
            $outer->where('changed_by', $user->id);

            if ($departmentIds !== null && $departmentIds !== []) {
                $outer->orWhereHas(
                    'transaction',
                    fn (Builder $transactionQuery) => $transactionQuery->whereIn('department_id', $departmentIds)
                );
            }
        });
    }

    public function showsPersonalLogOnly(User $user): bool
    {
        if ($this->canViewTeamLog($user)) {
            return false;
        }

        return ! $user->hasPermission('transactions.status.approve');
    }

    /** @param Builder<TransactionStatusHistory> $query */
    public function applyFilters(Builder $query, Request $request, User $user): void
    {
        $query->when($request->filled('search'), function (Builder $builder) use ($request) {
            $search = $request->string('search');

            $builder->whereHas('transaction', function (Builder $transactionQuery) use ($search) {
                $transactionQuery->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            });
        });

        $query->when($request->filled('action'), function (Builder $builder) use ($request) {
            $action = $request->string('action');

            if (WorkflowAction::tryFrom($action)) {
                $builder->where('action', $action);
            }
        });

        $query->when($request->filled('changed_by'), function (Builder $builder) use ($request) {
            $builder->where('changed_by', $request->integer('changed_by'));
        });

        $query->when($request->filled('department_id'), function (Builder $builder) use ($request, $user) {
            $departmentId = $request->integer('department_id');

            if ($user->canAccessDepartment($departmentId)) {
                $builder->whereHas('transaction', fn (Builder $transactionQuery) => $transactionQuery->where('department_id', $departmentId));
            }
        });

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }
    }

    /** @return Builder<TransactionStatusHistory> */
    public function baseQuery(User $user): Builder
    {
        $query = TransactionStatusHistory::query()
            ->where(function (Builder $builder) {
                $builder->whereIn('action', self::REVIEW_ACTIONS)
                    ->orWhere(function (Builder $legacy) {
                        $legacy->whereNull('action')
                            ->whereHas('fromStatus', fn (Builder $status) => $status->where('code', 'REVIEW'));
                    });
            })
            ->with([
                'transaction.department',
                'transaction.transactionType',
                'transaction.status',
                'fromStatus',
                'toStatus',
                'changedBy',
            ])
            ->orderByDesc('created_at');

        $this->applyScope($query, $user);

        return $query;
    }

    /** @return list<string> */
    public function reviewActions(): array
    {
        return self::REVIEW_ACTIONS;
    }
}
