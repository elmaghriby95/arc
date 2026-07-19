<?php

namespace App\Http\Controllers;

use App\Enums\LendingRequestStatus;
use App\Models\Department;
use App\Models\LendingRequest;
use App\Models\User;
use App\Services\LendingRequestService;
use App\Services\LendingScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LendingRequestController extends Controller
{
    public function index(Request $request, LendingScopeService $scope): View
    {
        $user = $request->user();

        $requests = LendingRequest::query()
            ->with([
                'transaction.department',
                'transaction.transactionType',
                'transaction.status',
                'requester',
            ])
            ->when(true, fn ($query) => $scope->applyScopeToQuery($query, $user))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->whereHas('transaction', function ($transactionQuery) use ($search) {
                    $transactionQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('department_id'), function ($query) use ($request, $user) {
                $departmentId = $request->integer('department_id');
                if ($user->canAccessDepartment($departmentId)) {
                    $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->where('department_id', $departmentId));
                }
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('lending-requests.index', [
            'requests' => $requests,
            'statuses' => $this->filterStatusesForUser($user, $scope),
            'orgUnits' => $this->scopedOrgUnitOptions($user),
        ]);
    }

    /** @return list<LendingRequestStatus> */
    private function filterStatusesForUser(User $user, LendingScopeService $scope): array
    {
        if ($scope->isStageLimitedActor($user)) {
            $allowed = $scope->visibleStatusesForDecisionActor($user);

            return array_values(array_filter(
                LendingRequestStatus::cases(),
                fn (LendingRequestStatus $status) => in_array($status->value, $allowed, true),
            ));
        }

        return LendingRequestStatus::cases();
    }

    public function show(
        LendingRequest $lendingRequest,
        LendingScopeService $scope,
        LendingRequestService $service,
    ): View {
        $user = auth()->user();

        if (! $scope->canViewRequest($user, $lendingRequest)) {
            abort(403);
        }

        $lendingRequest->load([
            'transaction.department',
            'transaction.transactionType',
            'transaction.status',
            'transaction.attachments' => fn ($query) => $query->latest(),
            'requester',
            'reviewer',
            'handoverBy',
            'returnedByUser',
            'histories.performer',
        ]);

        return view('lending-requests.show', [
            'lendingRequest' => $lendingRequest,
            'canReview' => $service->canReview($user, $lendingRequest),
            'canHandover' => $service->canHandover($user, $lendingRequest),
            'canReturn' => $service->canReturn($user, $lendingRequest),
        ]);
    }

    public function store(Request $request, LendingRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
            'purpose' => ['nullable', 'string', 'max:1000'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $transaction = \App\Models\Transaction::query()->findOrFail($validated['transaction_id']);

        if (! $request->user()->canAccessTransaction($transaction)) {
            abort(403);
        }

        $lendingRequest = $service->request(
            $request->user(),
            $transaction,
            $validated['purpose'] ?? null,
            $validated['due_date'],
        );

        if (! $lendingRequest) {
            return back()->with('error', __('lending_requests.messages.request_failed'));
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('lending_requests.messages.request_created'));
    }

    public function review(Request $request, LendingRequest $lendingRequest, LendingRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'approve' => ['required', 'in:0,1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approve = $validated['approve'] === '1';

        if (! $approve && blank($validated['notes'] ?? null)) {
            return back()
                ->withErrors(['notes' => __('validation.required', ['attribute' => __('lending_requests.reject_notes_required')])])
                ->withInput();
        }

        if (! $service->review(
            $request->user(),
            $lendingRequest,
            $approve,
            $validated['notes'] ?? null,
        )) {
            return redirect()
                ->route('lending-requests.index')
                ->with('error', __('lending_requests.messages.review_failed'));
        }

        return redirect()
            ->route('lending-requests.index')
            ->with(
                'success',
                $approve
                    ? __('lending_requests.messages.review_approved')
                    : __('lending_requests.messages.review_rejected'),
            );
    }

    public function handover(Request $request, LendingRequest $lendingRequest, LendingRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'in:0,1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $confirm = $validated['confirm'] === '1';

        if (! $confirm && blank($validated['notes'] ?? null)) {
            return back()
                ->withErrors(['notes' => __('validation.required', ['attribute' => __('lending_requests.reject_notes_required')])])
                ->withInput();
        }

        if (! $service->handover(
            $request->user(),
            $lendingRequest,
            $confirm,
            $validated['notes'] ?? null,
        )) {
            return redirect()
                ->route('lending-requests.index')
                ->with('error', __('lending_requests.messages.handover_failed'));
        }

        return redirect()
            ->route('lending-requests.index')
            ->with(
                'success',
                $confirm
                    ? __('lending_requests.messages.handover_confirmed')
                    : __('lending_requests.messages.handover_rejected'),
            );
    }

    public function returnDocuments(Request $request, LendingRequest $lendingRequest, LendingRequestService $service): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $service->returnDocuments(
            $request->user(),
            $lendingRequest,
            $validated['notes'] ?? null,
        )) {
            return redirect()
                ->route('lending-requests.index')
                ->with('error', __('lending_requests.messages.return_failed'));
        }

        return redirect()
            ->route('lending-requests.index')
            ->with('success', __('lending_requests.messages.returned'));
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
