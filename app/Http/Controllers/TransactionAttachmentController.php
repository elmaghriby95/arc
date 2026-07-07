<?php

namespace App\Http\Controllers;

use App\Models\TransactionAttachment;
use App\Models\Transaction;
use App\Services\TransactionAttachmentCreator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionAttachmentController extends Controller
{
    public function storeCreateUpload(
        Request $request,
        Transaction $transaction,
        TransactionAttachmentCreator $attachmentCreator,
    ): JsonResponse {
        $this->authorizeAccess($transaction);
        $this->authorizeCreateFlowUpload($request, $transaction);

        $maxFileKb = (int) config('uploads.max_file_kb', 65536);

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.$maxFileKb,
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'reference_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'reference_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'original_document_number' => ['nullable', 'string', 'max:255'],
            'use_operational' => ['nullable', 'boolean'],
        ]);

        $attachment = $attachmentCreator->create(
            $transaction,
            $validated['file'],
            $validated,
            $request->user(),
        );

        return response()->json([
            'attachment_id' => $attachment->id,
            'transaction_id' => $transaction->id,
        ]);
    }

    public function storeUpload(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->authorizeMutation($transaction);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:'.(int) config('uploads.max_file_kb', 65536),
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']),
            ],
            'titles' => ['nullable', 'array'],
            'titles.*' => ['nullable', 'string', 'max:255'],
        ]);

        $sortOrder = (int) $transaction->attachments()->max('sort_order');

        foreach ($validated['files'] as $index => $file) {
            $sortOrder++;
            $path = $file->store('transaction-attachments/'.$transaction->id, 'local');

            $transaction->attachments()->create([
                'title' => $validated['titles'][$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $path,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $request->user()->id,
                'sort_order' => $sortOrder,
            ]);
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('messages.transaction_attachment.uploaded'));
    }

    public function destroy(Transaction $transaction, TransactionAttachment $attachment): RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->ensureAttachmentBelongsToTransaction($transaction, $attachment);

        if (! auth()->user()?->hasPermission('transactions.edit')) {
            abort(403, __('messages.transaction_attachment.no_permission'));
        }

        if (! $attachment->canBeDeletedBy(auth()->user())) {
            return redirect()
                ->route('transactions.show', $transaction)
                ->with('error', __('messages.transaction_attachment.delete_draft_only'));
        }

        if ($attachment->file_path && Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', __('messages.transaction_attachment.deleted'));
    }

    public function download(Transaction $transaction, TransactionAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->ensureAttachmentBelongsToTransaction($transaction, $attachment);

        $path = $attachment->effectiveFilePath();
        $name = $attachment->effectiveFileName() ?? $attachment->displayName();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', __('messages.file_not_found'));
        }

        return Storage::disk('local')->download($path, $name);
    }

    public function preview(Transaction $transaction, TransactionAttachment $attachment)
    {
        $this->authorizeAccess($transaction);
        $this->ensureAttachmentBelongsToTransaction($transaction, $attachment);

        if (! $attachment->isImage()) {
            abort(404);
        }

        $path = $attachment->effectiveFilePath();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $attachment->effectiveMimeType() ?? 'image/jpeg',
        ]);
    }

    private function authorizeAccess(Transaction $transaction): void
    {
        if (! auth()->user()?->canAccessTransaction($transaction)) {
            abort(403, __('messages.transaction.access_denied'));
        }
    }

    private function authorizeMutation(Transaction $transaction): void
    {
        if (! auth()->user()?->hasPermission('transactions.edit')) {
            abort(403, __('messages.transaction_attachment.no_permission'));
        }

        if (! $transaction->canBeEdited()) {
            abort(403, __('messages.transaction_attachment.draft_only_mutation'));
        }
    }

    private function authorizeCreateFlowUpload(Request $request, Transaction $transaction): void
    {
        if (! $transaction->canBeEdited()) {
            abort(403, __('messages.transaction_attachment.draft_only_mutation'));
        }

        $user = $request->user();

        if ($user?->hasPermission('transactions.edit')) {
            return;
        }

        if (
            $user?->hasPermission('transactions.create')
            && (int) $transaction->created_by === (int) $user->id
        ) {
            return;
        }

        abort(403, __('messages.transaction_attachment.no_permission'));
    }

    private function ensureAttachmentBelongsToTransaction(Transaction $transaction, TransactionAttachment $attachment): void
    {
        if ($attachment->transaction_id !== $transaction->id) {
            abort(404);
        }
    }
}
