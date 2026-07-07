<?php

namespace App\Http\Controllers;

use App\Enums\LendingRequestAction;
use App\Models\Department;
use App\Models\LendingRequestHistory;
use App\Models\User;
use App\Services\LendingScopeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LendingLogController extends Controller
{
    public function index(Request $request, LendingScopeService $scope): View
    {
        $user = $request->user();

        $query = LendingRequestHistory::query()
            ->with([
                'lendingRequest.transaction.department',
                'lendingRequest.transaction.transactionType',
                'lendingRequest.requester',
                'performer',
            ])
            ->whereHas('lendingRequest', function ($lendingQuery) use ($scope, $user) {
                $scope->applyScopeToQuery($lendingQuery, $user);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->whereHas('lendingRequest.transaction', function ($transactionQuery) use ($search) {
                    $transactionQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->when($request->filled('department_id'), function ($query) use ($request, $user) {
                $departmentId = $request->integer('department_id');
                if ($user->canAccessDepartment($departmentId)) {
                    $query->whereHas(
                        'lendingRequest.transaction',
                        fn ($transactionQuery) => $transactionQuery->where('department_id', $departmentId),
                    );
                }
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->string('date_to')))
            ->orderByDesc('created_at');

        $entries = $query->paginate(20)->withQueryString();

        return view('lending-requests.log', [
            'entries' => $entries,
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'actions' => LendingRequestAction::cases(),
        ]);
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user): array
    {
        $options = Department::optionsForSelect();
        $ids = $user->orgScopeDepartmentIds();

        if ($ids !== null) {
            $ids = array_map(intval(...), $ids);
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array((int) $option['id'], $ids, true)
            ));
        }

        return $options;
    }
}
