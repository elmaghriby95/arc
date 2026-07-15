<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowAction;
use App\Models\Department;
use App\Models\Folder;
use App\Models\ReferenceNumberSetting;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\LendingEligibilityService;
use App\Services\ReferenceNumberService;
use App\Services\TransactionAttachmentCreator;
use App\Services\TransactionQrCodeService;
use App\Services\TransactionScopeService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request, TransactionScopeService $scope): View
    {
        $user = $request->user();

        $transactions = $this->scopedTransactionsQuery($user, $scope)
            ->with(['department', 'folder', 'transactionType', 'status', 'creator'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('archival_reference', 'like', "%{$search}%")
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
            'statuses' => TransactionStatus::where('is_active', true)
                ->whereIn('id', $scope->visibleStatusIds($user))
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(Request $request, ReferenceNumberService $referenceNumbers): View
    {
        $user = $request->user();
        $initialStatus = TransactionStatus::initial();
        $departmentIds = $user->orgScopeDepartmentIds();

        return view('transactions.create', [
            'orgUnits' => $this->scopedOrgUnitOptions($user, forMutation: true),
            'folders' => $this->scopedFolders($user),
            'folderTree' => Folder::scopedTree($departmentIds, activeOnly: true),
            'departmentBreadcrumbs' => Department::breadcrumbMap(),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
            'initialStatus' => $initialStatus,
            'workflow' => TransactionStatus::workflowSequence(),
            'defaultDepartmentId' => $user->department_id,
            'referenceSettings' => ReferenceNumberSetting::instance(),
            'referenceFormConfig' => $referenceNumbers->formConfig($user),
            'txnCreateI18n' => $this->transactionCreateJsI18n(),
            'uploadLimits' => $this->transactionUploadLimits(),
        ]);
    }

    public function store(Request $request, TransactionAttachmentCreator $attachmentCreator): RedirectResponse|JsonResponse
    {
        $initialStatus = TransactionStatus::initial();

        if (! $initialStatus) {
            return $this->storeFailureResponse(
                $request,
                ['transaction_status' => [__('messages.transaction.statuses_required')]],
                __('messages.transaction.statuses_required'),
            );
        }

        $resumableDraft = $this->findResumableDraft($request);

        $archivalReferenceRules = ['required', 'string', 'max:255'];

        if ($resumableDraft) {
            $archivalReferenceRules[] = Rule::unique('transactions', 'archival_reference')->ignore($resumableDraft->id);
        } else {
            $archivalReferenceRules[] = 'unique:transactions,archival_reference';
        }

        $maxFileKb = $this->maxUploadFileKb();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'archival_reference' => $archivalReferenceRules,
            'description' => ['nullable', 'string'],
            'transaction_type_id' => ['nullable', 'exists:transaction_types,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'folder_id' => ['required', 'exists:folders,id'],
            'transaction_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:'.$maxFileKb,
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
        ], [
            'archival_reference.unique' => __('validation.unique', ['attribute' => __('validation.attributes.archival_reference')]),
            'files.required' => __('messages.transaction.documents_required'),
            'files.min' => __('messages.transaction.documents_required'),
            'files.*.required' => __('messages.transaction.documents_required'),
        ]);

        if (! $request->user()->canAccessDepartment($validated['department_id'])) {
            return $this->storeFailureResponse(
                $request,
                ['department_id' => [__('messages.transaction.department_create_denied')]],
            );
        }

        if ($folderError = $this->validateTransactionFolder($validated['folder_id'], $validated['department_id'], $request->user())) {
            return $this->storeFailureResponse(
                $request,
                ['folder_id' => [$folderError]],
            );
        }

        $transaction = $resumableDraft ?? Transaction::create([
            'reference_number' => $this->generateReferenceNumber(),
            'archival_reference' => $validated['archival_reference'],
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

        if ($resumableDraft) {
            $transaction->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'transaction_type_id' => $validated['transaction_type_id'] ?? null,
                'department_id' => $validated['department_id'],
                'folder_id' => $validated['folder_id'],
                'transaction_date' => $validated['transaction_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } else {
            $transaction->statusHistories()->create([
                'from_status_id' => null,
                'to_status_id' => $initialStatus->id,
                'changed_by' => $request->user()->id,
                'action' => WorkflowAction::Create->value,
                'notes' => __('messages.transaction.created_note'),
            ]);
        }

        if (! empty($validated['files'])) {
            $sortOrder = 0;

            foreach ($validated['files'] as $index => $file) {
                $sortOrder++;
                $attachmentCreator->create(
                    $transaction,
                    $file,
                    [
                        'title' => $validated['titles'][$index] ?? null,
                        'reference_number' => $validated['reference_numbers'][$index] ?? null,
                        'reference_year' => $validated['reference_years'][$index] ?? null,
                        'reference_month' => $validated['reference_months'][$index] ?? null,
                        'original_document_number' => $validated['original_document_numbers'][$index] ?? null,
                        'use_operational' => $validated['use_operational'][$index] ?? false,
                    ],
                    $request->user(),
                    $sortOrder,
                );
            }
        }

        return $this->storeSuccessResponse($request, $transaction);
    }

    public function show(Transaction $transaction, WorkflowService $workflow, LendingEligibilityService $lendingEligibility, TransactionQrCodeService $qrCodes): View
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
            'lendingRequests.histories.performer',
        ]);

        $user = auth()->user();

        return view('transactions.show', [
            'transaction' => $transaction,
            'statusTimeline' => $transaction->statusTimeline(),
            'workflow' => TransactionStatus::workflowSequence(),
            'workflowActions' => $workflow->availableActions($transaction, $user),
            'canManageAttachments' => $user->hasPermission('transactions.edit') && $transaction->canBeEdited(),
            'txnAttachmentsI18n' => $this->transactionAttachmentsJsI18n(),
            'canShowLendingButton' => $lendingEligibility->canShowRequestButton($user, $transaction),
            'canRequestLending' => $lendingEligibility->canUserRequest($user, $transaction),
            'lendingRequestBlockReason' => $lendingEligibility->blockingReason($user, $transaction),
            'qrPayload' => $qrCodes->payload($transaction),
            'qrDisplaySize' => $qrCodes->displaySize(),
        ]);
    }

    public function qrCode(Transaction $transaction, TransactionQrCodeService $qrCodes): Response
    {
        $this->authorizeTransactionAccess($transaction);

        return response($qrCodes->svg($transaction, $qrCodes->displaySize()), 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function qrPrint(Transaction $transaction, TransactionQrCodeService $qrCodes): View
    {
        $this->authorizeTransactionAccess($transaction);

        return view('transactions.qr-print', [
            'qrPayload' => $qrCodes->payload($transaction),
            'qrSvg' => $qrCodes->svg($transaction, $qrCodes->printSize()),
            'printSize' => $qrCodes->printSize(),
        ]);
    }

    public function edit(Transaction $transaction): View|RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        if (! $transaction->canBeEdited()) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', __('messages.transaction.edit_draft_only'));
        }

        $user = auth()->user();

        return view('transactions.edit', [
            'transaction' => $transaction,
            'orgUnits' => $this->scopedOrgUnitOptions($user, forMutation: true),
            'folders' => $this->scopedFolders($user),
            'transactionTypes' => TransactionType::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        if (! $transaction->canBeEdited()) {
            return back()->with('error', __('messages.transaction.edit_draft_only'));
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'archival_reference' => ['required', 'string', 'max:255', Rule::unique('transactions', 'archival_reference')->ignore($transaction->id)],
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
                ->withErrors(['department_id' => __('messages.transaction.department_move_denied')]);
        }

        if ($folderError = $this->validateTransactionFolder($validated['folder_id'], $validated['department_id'], $request->user())) {
            return back()
                ->withInput()
                ->withErrors(['folder_id' => $folderError]);
        }

        $transaction->update([
            'title' => $validated['title'],
            'archival_reference' => $validated['archival_reference'],
            'description' => $validated['description'] ?? null,
            'transaction_type_id' => $validated['transaction_type_id'] ?? null,
            'department_id' => $validated['department_id'],
            'folder_id' => $validated['folder_id'],
            'transaction_date' => $validated['transaction_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('messages.transaction.updated'));
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', __('messages.transaction.deleted'));
    }

    public function transition(Request $request, Transaction $transaction, WorkflowService $workflow): RedirectResponse
    {
        $this->authorizeTransactionAccess($transaction);

        $validated = $request->validate([
            'action' => ['required', Rule::enum(WorkflowAction::class)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $action = WorkflowAction::from($validated['action']);

        if ($action === WorkflowAction::Reject && blank($validated['notes'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['notes' => __('messages.transaction.reject_notes_required')]);
        }

        if (! $workflow->transition($transaction, $action, $request->user(), $validated['notes'] ?? null)) {
            return back()->with('error', __('messages.transaction.action_denied'));
        }

        $message = match ($action) {
            WorkflowAction::Reject => __('messages.transaction.rejected'),
            WorkflowAction::Submit => __('messages.transaction.submitted'),
            default => __('messages.transaction.status_updated'),
        };

        $user = $request->user();

        if ($user->canAccessTransaction($transaction)) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('success', $message);
        }

        if (
            in_array($action, [WorkflowAction::Approve, WorkflowAction::Reject], true)
            && $user->hasPermission('transactions.review-log.view')
        ) {
            return redirect()
                ->route('transactions.review-log')
                ->with('success', $message);
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', $message);
    }

    private function generateReferenceNumber(): string
    {
        do {
            $reference = 'TXN-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Transaction::where('reference_number', $reference)->exists());

        return $reference;
    }

    private function scopedTransactionsQuery(User $user, TransactionScopeService $scope)
    {
        $query = Transaction::query();
        $scope->applyScopeToQuery($query, $user);

        return $query;
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    private function scopedOrgUnitOptions(User $user, bool $forMutation = false): array
    {
        $options = Department::optionsForSelect();

        $ids = $forMutation
            ? $user->orgScopeDepartmentIds()
            : $user->transactionOrgScopeDepartmentIds();

        if ($ids !== null) {
            $ids = array_map(intval(...), $ids);
            $options = array_values(array_filter(
                $options,
                fn (array $option) => in_array((int) $option['id'], $ids, true)
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
            return __('messages.folder.unavailable');
        }

        if (! $user->canAccessFolder($folder)) {
            return __('messages.folder.out_of_scope');
        }

        if ((int) $folder->department_id !== (int) $departmentId) {
            return __('messages.folder.wrong_department');
        }

        return null;
    }

    private function authorizeTransactionAccess(Transaction $transaction): void
    {
        if (! auth()->user()?->canAccessTransaction($transaction)) {
            abort(403, __('messages.transaction.access_denied'));
        }
    }

    /** @param  array<string, list<string>>  $errors */
    private function storeFailureResponse(Request $request, array $errors, ?string $message = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message ?? __('transactions.validation_heading'),
                'errors' => $errors,
            ], 422);
        }

        $response = back()->withInput()->withErrors($errors);

        if ($message) {
            $response = $response->with('error', $message);
        }

        return $response;
    }

    private function storeSuccessResponse(Request $request, Transaction $transaction): RedirectResponse|JsonResponse
    {
        $url = route('transactions.show', $transaction);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $url,
                'transaction_id' => $transaction->id,
            ]);
        }

        return redirect($url)->with('success', __('messages.transaction.created'));
    }

    /** @return array{max_file_bytes: int, php_upload_bytes: int, php_post_bytes: int} */
    private function transactionUploadLimits(): array
    {
        $maxFileBytes = $this->maxUploadFileKb() * 1024;
        $phpUploadBytes = $this->iniSizeToBytes(ini_get('upload_max_filesize'));
        $phpPostBytes = $this->iniSizeToBytes(ini_get('post_max_size'));

        $effectiveMax = $maxFileBytes;

        if ($phpUploadBytes > 0) {
            $effectiveMax = min($effectiveMax, $phpUploadBytes);
        }

        return [
            'max_file_bytes' => $effectiveMax,
            'php_upload_bytes' => $phpUploadBytes,
            'php_post_bytes' => $phpPostBytes,
        ];
    }

    private function maxUploadFileKb(): int
    {
        return (int) config('uploads.max_file_kb', 65536);
    }

    private function findResumableDraft(Request $request): ?Transaction
    {
        if (! $request->expectsJson() || $request->hasFile('files')) {
            return null;
        }

        $reference = trim((string) $request->input('archival_reference', ''));

        if ($reference === '') {
            return null;
        }

        return Transaction::query()
            ->where('archival_reference', $reference)
            ->where('created_by', $request->user()->id)
            ->whereHas('status', fn ($query) => $query->where('is_initial', true))
            ->first();
    }

    private function iniSizeToBytes(string|false $value): int
    {
        if (! is_string($value) || $value === '') {
            return 0;
        }

        $value = trim($value);
        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /** @return array<string, string> */
    private function transactionCreateJsI18n(): array
    {
        return [
            'select_folder_required' => __('transactions.js.select_folder_required'),
            'no_folder_selected' => __('transactions.js.no_folder_selected'),
            'unsupported_file_type' => __('transactions.js.unsupported_file_type'),
            'files_rejected' => __('transactions.js.files_rejected'),
            'folder_unit_mismatch' => __('transactions.js.folder_unit_mismatch'),
            'documents_required' => __('transactions.js.documents_required'),
            'field_year' => __('transactions.js.field_year'),
            'field_month' => __('transactions.js.field_month'),
            'field_month_required' => __('transactions.js.field_month_required'),
            'field_original' => __('transactions.js.field_original'),
            'field_original_required' => __('transactions.js.field_original_required'),
            'field_operational' => __('transactions.js.field_operational'),
            'field_title' => __('transactions.js.field_title'),
            'field_reference_number' => __('transactions.js.field_reference_number'),
            'field_reference_placeholder' => __('transactions.js.field_reference_placeholder'),
            'delete' => __('transactions.js.delete'),
            'upload_progress' => __('transactions.js.upload_progress'),
            'upload_preparing' => __('transactions.js.upload_preparing'),
            'upload_failed' => __('transactions.js.upload_failed'),
            'upload_too_large' => __('transactions.js.upload_too_large'),
            'upload_server_limit' => __('transactions.js.upload_server_limit'),
            'upload_creating' => __('transactions.js.upload_creating'),
            'upload_partial' => __('transactions.js.upload_partial'),
            'validation_heading' => __('transactions.js.validation_heading'),
        ];
    }

    /** @return array<string, string> */
    private function transactionAttachmentsJsI18n(): array
    {
        return [
            'remove' => __('transactions.js.remove'),
            'unsupported_file_type' => __('transactions.js.unsupported_file_type'),
            'files_rejected' => __('transactions.js.files_rejected'),
        ];
    }
}
