<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Folder;
use App\Models\ReferenceNumberSetting;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\ReferenceNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
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

    public function create(Request $request, ReferenceNumberService $referenceNumbers): View
    {
        $user = $request->user();
        $initialStatus = TransactionStatus::initial();
        $departmentIds = $user->orgScopeDepartmentIds();

        return view('transactions.create', [
            'orgUnits' => $this->scopedOrgUnitOptions($user),
            'folders' => $this->scopedFolders($user),
            'folderTree' => Folder::scopedTree($departmentIds, activeOnly: true),
            'departmentBreadcrumbs' => Department::breadcrumbMap(),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
            'initialStatus' => $initialStatus,
            'workflow' => TransactionStatus::workflowSequence(),
            'defaultDepartmentId' => $user->department_id,
            'referenceSettings' => ReferenceNumberSetting::instance(),
            'referenceFormConfig' => $referenceNumbers->formConfig($user),
        ]);
    }

    public function store(Request $request, ReferenceNumberService $referenceNumbers): RedirectResponse
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
            'folder_id' => ['required', 'exists:folders,id'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => [
                'file',
                'max:20480',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']),
            ],
            'titles' => ['nullable', 'array'],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'reference_numbers' => ['nullable', 'array'],
            'reference_numbers.*' => ['nullable', 'string', 'max:255'],
            'reference_years' => ['nullable', 'array'],
            'reference_years.*' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'reference_months' => ['nullable', 'array'],
            'reference_months.*' => ['nullable', 'integer', 'min:1', 'max:12'],
            'original_document_numbers' => ['nullable', 'array'],
            'original_document_numbers.*' => ['nullable', 'string', 'max:255'],
            'use_operational' => ['nullable', 'array'],
            'use_operational.*' => ['nullable', 'boolean'],
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'])) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك إنشاء معاملة في هذه الوحدة التنظيمية.']);
        }

        if ($folderError = $this->validateTransactionFolder($validated['folder_id'], $validated['department_id'], $request->user())) {
            return back()
                ->withInput()
                ->withErrors(['folder_id' => $folderError]);
        }

        $transaction = Transaction::create([
            'reference_number' => $this->generateReferenceNumber(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'transaction_type_id' => $validated['transaction_type_id'] ?? null,
            'department_id' => $validated['department_id'],
            'folder_id' => $validated['folder_id'],
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

        $department = Department::findOrFail($validated['department_id']);
        $transactionType = isset($validated['transaction_type_id'])
            ? TransactionType::find($validated['transaction_type_id'])
            : null;

        if (! empty($validated['files'])) {
            $sortOrder = 0;

            foreach ($validated['files'] as $index => $file) {
                $sortOrder++;
                $path = $file->store('transaction-attachments/'.$transaction->id, 'local');

                $referenceData = $referenceNumbers->resolveForAttachment(
                    [
                        'reference_number' => $validated['reference_numbers'][$index] ?? null,
                        'reference_year' => $validated['reference_years'][$index] ?? null,
                        'reference_month' => $validated['reference_months'][$index] ?? null,
                        'original_document_number' => $validated['original_document_numbers'][$index] ?? null,
                        'use_operational' => $validated['use_operational'][$index] ?? false,
                    ],
                    $department,
                    $transactionType,
                    $request->user(),
                );

                $transaction->attachments()->create([
                    'title' => $validated['titles'][$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'reference_number' => $referenceData['reference_number'],
                    'reference_year' => $referenceData['reference_year'],
                    'reference_month' => $referenceData['reference_month'],
                    'original_document_number' => $referenceData['original_document_number'],
                    'is_operational_number' => $referenceData['is_operational_number'],
                    'file_path' => $path,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => $request->user()->id,
                    'sort_order' => $sortOrder,
                ]);
            }
        }

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
            'folder_id' => ['required', 'exists:folders,id'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'])) {
            return back()
                ->withInput()
                ->withErrors(['department_id' => 'لا يمكنك نقل المعاملة إلى هذه الوحدة التنظيمية.']);
        }

        if ($folderError = $this->validateTransactionFolder($validated['folder_id'], $validated['department_id'], $request->user())) {
            return back()
                ->withInput()
                ->withErrors(['folder_id' => $folderError]);
        }

        $transaction->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'transaction_type_id' => $validated['transaction_type_id'] ?? null,
            'department_id' => $validated['department_id'],
            'folder_id' => $validated['folder_id'],
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

    private function validateTransactionFolder(int $folderId, int $departmentId, User $user): ?string
    {
        $folder = Folder::find($folderId);

        if (! $folder || ! $folder->is_active) {
            return 'المجلد المحدد غير متاح.';
        }

        if (! $user->canAccessFolder($folder)) {
            return 'المجلد المحدد غير متاح ضمن نطاقك التنظيمي.';
        }

        if ((int) $folder->department_id !== (int) $departmentId) {
            return 'المجلد المحدد لا ينتمي للوحدة التنظيمية المختارة.';
        }

        return null;
    }

    private function authorizeTransactionAccess(Transaction $transaction): void
    {
        if (! auth()->user()?->canAccessTransaction($transaction)) {
            abort(403, 'لا يمكنك الوصول إلى هذه المعاملة ضمن نطاقك التنظيمي.');
        }
    }
}
