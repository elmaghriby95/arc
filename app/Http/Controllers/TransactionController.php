<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Folder;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $transactions = $this->scopedTransactionsQuery($user)
            ->with(['department', 'folder', 'transactionType', 'status', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('department_id'), function ($query) use ($request, $user) {
                $departmentId = $request->integer('department_id');
                if ($user->canAccessDepartment($departmentId)) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->when($request->filled('folder_id'), fn ($q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('transaction_type_id'), fn ($q) => $q->where('transaction_type_id', $request->integer('transaction_type_id')))
            ->when($request->filled('transaction_status_id'), fn ($q) => $q->where('transaction_status_id', $request->integer('transaction_status_id')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', [
            'transactions' => $transactions,
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'folders' => $this->scopedFolders($user),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
            'statuses' => TransactionStatus::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $initialStatus = TransactionStatus::initial();

        return view('transactions.create', [
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'folders' => $this->scopedFolders($user),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
            'initialStatus' => $initialStatus,
            'defaultDepartmentId' => $user->department_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $initialStatus = TransactionStatus::initial();

        if (! $initialStatus) {
            return back()
                ->withInput()
                ->with('error', 'يجب إعداد حالات المعاملات في الإعدادات قبل إنشاء معاملة.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'transaction_type_id' => ['nullable', 'exists:transaction_types,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'])) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك إنشاء معاملة في هذه الوحدة التنظيمية.']);
        }

        if (! empty($validated['folder_id']) && ! $this->folderBelongsToDepartment($validated['folder_id'], $validated['department_id'], $request->user())) {
            return back()
                ->withInput()
                ->withErrors(['folder_id' => 'المجلد المحدد لا ينتمي لوحدتك التنظيمية.']);
        }

        $transaction = Transaction::create([
            'reference_number' => $this->generateReferenceNumber(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'transaction_type_id' => $validated['transaction_type_id'] ?? null,
            'department_id' => $validated['department_id'],
            'folder_id' => $validated['folder_id'] ?? null,
            'transaction_status_id' => $initialStatus->id,
            'created_by' => $request->user()->id,
            'transaction_date' => $validated['transaction_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $transaction->statusHistories()->create([
            'from_status_id' => null,
            'to_status_id' => $initialStatus->id,
            'changed_by' => $request->user()->id,
            'notes' => 'إنشاء المعاملة',
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'تم إنشاء المعاملة بنجاح.');
    }

    public function show(Transaction $transaction): View
    {
        $this->authorizeTransactionAccess($transaction);

        $transaction->load([
            'department',
            'folder',
            'transactionType',
            'status',
            'creator',
            'attachments.uploader',
            'statusHistories.fromStatus',
            'statusHistories.toStatus',
            'statusHistories.changedBy',
        ]);

        $user = auth()->user();

        $workflow = TransactionStatus::workflowSequence();

        return view('transactions.show', [
            'transaction' => $transaction,
            'workflow' => $workflow,
            'nextStatus' => $transaction->nextStatus(),
            'canAdvance' => $transaction->canUserAdvance($user),
            'canManageAttachments' => $user->hasPermission('transactions.edit') && ! $transaction->isAtFinalStatus(),
        ]);
    }

    public function edit(Transaction $transaction): View|RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        if ($transaction->isAtFinalStatus()) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', 'لا يمكن تعديل معاملة في حالة نهائية.');
        }

        $user = auth()->user();

        return view('transactions.edit', [
            'transaction' => $transaction,
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'folders' => $this->scopedFolders($user),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        if ($transaction->isAtFinalStatus()) {
            return back()->with('error', 'لا يمكن تعديل معاملة في حالة نهائية.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'transaction_type_id' => ['nullable', 'exists:transaction_types,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'folder_id' => ['nullable', 'exists:folders,id'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'])) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك نقل المعاملة إلى هذه الوحدة التنظيمية.']);
        }

        if (! empty($validated['folder_id']) && ! $this->folderBelongsToDepartment($validated['folder_id'], $validated['department_id'], $request->user())) {
            return back()
                ->withInput()
                ->withErrors(['folder_id' => 'المجلد المحدد لا ينتمي لوحدتك التنظيمية.']);
        }

        $transaction->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'transaction_type_id' => $validated['transaction_type_id'] ?? null,
            'department_id' => $validated['department_id'],
            'folder_id' => $validated['folder_id'] ?? null,
            'transaction_date' => $validated['transaction_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'تم تحديث المعاملة بنجاح.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', 'تم حذف المعاملة بنجاح.');
    }

    public function advanceStatus(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $transaction->advanceStatus($request->user(), $validated['notes'] ?? null)) {
            return back()->with('error', 'لا تملك صلاحية الانتقال إلى الحالة التالية أو أن المعاملة في آخر مرحلة.');
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'تم تحديث حالة المعاملة بنجاح.');
    }

    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'TXN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Transaction::where('reference_number', $reference)->exists());

        return $reference;
    }

    private function scopedTransactionsQuery(User $user)
    {
        $query = Transaction::query();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $query->whereIn('department_id', $ids);
        }

        return $query;
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user): array
    {
        $options = Department::optionsForSelect();

        if ($ids = $user->orgScopeDepartmentIds()) {
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array($option['id'], $ids, true)
            ));
        }

        return $options;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Folder> */
    private function scopedFolders(User $user)
    {
        return Folder::scopedQuery($user->orgScopeDepartmentIds())
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function folderBelongsToDepartment(int $folderId, int $departmentId, User $user): bool
    {
        $folder = Folder::find($folderId);

        if (! $folder || ! $user->canAccessFolder($folder)) {
            return false;
        }

        return $folder->department_id === $departmentId;
    }

    private function authorizeTransactionAccess(Transaction $transaction): void
    {
        if (! auth()->user()?->canAccessTransaction($transaction)) {
            abort(403, 'لا يمكنك الوصول إلى هذه المعاملة ضمن نطاقك التنظيمي.');
        }
    }
}
