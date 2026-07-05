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
        WorkflowAction::Submit->value,
        WorkflowAction::Approve->value,
        WorkflowAction::Reject->value,
    ];

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
            ->whereIn('action', self::REVIEW_ACTIONS)
            ->with([
                'transaction.department',
                'transaction.transactionType',
                'transaction.status',
                'fromStatus',
                'toStatus',
                'changedBy',
            ])
            ->orderByDesc('created_at');

        if (! $user->hasPermission('transactions.view-all')) {
            $departmentIds = $user->transactionOrgScopeDepartmentIds();

            $query->whereHas('transaction', function (Builder $transactionQuery) use ($departmentIds) {
                if ($departmentIds !== null) {
                    $transactionQuery->whereIn('department_id', $departmentIds);
                }
            });
        }

        return $query;
    }
}
