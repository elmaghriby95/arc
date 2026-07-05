<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowAction;
use App\Models\Department;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use App\Services\TransactionReviewLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionReviewLogController extends Controller
{
    public function index(Request $request, TransactionReviewLogService $reviewLog): View
    {
        $user = $request->user();

        $query = $reviewLog->baseQuery($user);
        $reviewLog->applyFilters($query, $request, $user);

        $entries = $query->paginate(20)->withQueryString();

        return view('transactions.review-log', [
            'entries' => $entries,
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'reviewers' => $this->scopedReviewers($user),
            'actions' => [
                WorkflowAction::Submit,
                WorkflowAction::Approve,
                WorkflowAction::Reject,
            ],
        ]);
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user): array
    {
        $options = Department::optionsForSelect();
        $ids = $user->transactionOrgScopeDepartmentIds();

        if ($ids !== null) {
            $ids = array_map(intval(...), $ids);
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array((int) $option['id'], $ids, true)
            ));
        }

        return $options;
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function scopedReviewers(User $user)
    {
        $historyQuery = TransactionStatusHistory::query()
            ->whereIn('action', [
                WorkflowAction::Submit->value,
                WorkflowAction::Approve->value,
                WorkflowAction::Reject->value,
            ]);

        if (! $user->hasPermission('transactions.view-all')) {
            $departmentIds = $user->transactionOrgScopeDepartmentIds();

            if ($departmentIds === []) {
                return collect();
            }

            $historyQuery->whereHas('transaction', function ($query) use ($departmentIds) {
                if ($departmentIds !== null) {
                    $query->whereIn('department_id', $departmentIds);
                }
            });
        }

        $reviewerIds = $historyQuery
            ->distinct()
            ->pluck('changed_by')
            ->filter();

        return User::query()
            ->whereIn('id', $reviewerIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
