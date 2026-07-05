<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TransactionStatus;
use App\Services\WorkflowPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionStatusController extends Controller
{
    public function index(): View
    {
        return view('settings.transaction-statuses.index', [
            'statuses' => TransactionStatus::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, WorkflowPermissionService $workflowPermissions): RedirectResponse
    {
        $validated = $this->validateStatus($request);

        $isInitial = $request->boolean('is_initial');

        if ($isInitial) {
            TransactionStatus::where('is_initial', true)->update(['is_initial' => false]);
        }

        $status = TransactionStatus::create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? $this->nextSortOrder(),
            'is_initial' => $isInitial,
            'is_final' => $request->boolean('is_final'),
            'is_active' => $request->boolean('is_active', true),
            'required_permission' => null,
        ]);

        $workflowPermissions->sync($status);

        return redirect()
            ->route('settings.transaction-statuses.index')
            ->with('success', 'تم إضافة حالة المعاملة بنجاح.');
    }

    public function update(Request $request, TransactionStatus $transactionStatus, WorkflowPermissionService $workflowPermissions): RedirectResponse
    {
        $validated = $this->validateStatus($request, $transactionStatus);

        $isInitial = $request->boolean('is_initial');
        $previousPermissionKey = $transactionStatus->required_permission;

        if ($isInitial && ! $transactionStatus->is_initial) {
            TransactionStatus::where('is_initial', true)->update(['is_initial' => false]);
        }

        $transactionStatus->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? $transactionStatus->sort_order,
            'is_initial' => $isInitial,
            'is_final' => $request->boolean('is_final'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $workflowPermissions->sync($transactionStatus->fresh(), $previousPermissionKey);

        return redirect()
            ->route('settings.transaction-statuses.index')
            ->with('success', 'تم تحديث حالة المعاملة بنجاح.');
    }

    public function destroy(TransactionStatus $transactionStatus, WorkflowPermissionService $workflowPermissions): RedirectResponse
    {
        if ($transactionStatus->transactions()->exists()) {
            return redirect()
                ->route('settings.transaction-statuses.index')
                ->with('error', 'لا يمكن حذف حالة مرتبطة بمعاملات.');
        }

        if ($transactionStatus->is_initial) {
            return redirect()
                ->route('settings.transaction-statuses.index')
                ->with('error', 'لا يمكن حذف الحالة الابتدائية. عيّن حالة أخرى كابتدائية أولاً.');
        }

        $permissionKey = $transactionStatus->required_permission;
        $transactionStatus->delete();

        if ($permissionKey) {
            $workflowPermissions->stripPermissionFromAllRoles($permissionKey);
        }

        return redirect()
            ->route('settings.transaction-statuses.index')
            ->with('success', 'تم حذف حالة المعاملة بنجاح.');
    }

    /** @return array<string, mixed> */
    private function validateStatus(Request $request, ?TransactionStatus $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('transaction_statuses', 'code')->ignore($existing?->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
    }

    private function nextSortOrder(): int
    {
        return (int) TransactionStatus::max('sort_order') + 1;
    }
}
