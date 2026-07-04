<?php

namespace App\Http\Controllers;

use App\Models\TransactionAttachment;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionAttachmentController extends Controller
{
    public function storeUpload(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->authorizeMutation($transaction);

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:20480',
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
            ->with('success', 'تم رفع المستندات بنجاح.');
    }

    public function destroy(Transaction $transaction, TransactionAttachment $attachment): RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->authorizeMutation($transaction);
        $this->ensureAttachmentBelongsToTransaction($transaction, $attachment);

        if ($attachment->file_path && Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('success', 'تم حذف المرفق بنجاح.');
    }

    public function download(Transaction $transaction, TransactionAttachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorizeAccess($transaction);
        $this->ensureAttachmentBelongsToTransaction($transaction, $attachment);

        $path = $attachment->effectiveFilePath();
        $name = $attachment->effectiveFileName() ?? $attachment->displayName();

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return back()->with('error', 'الملف غير موجود.');
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
            abort(403, 'لا يمكنك الوصول إلى هذه المعاملة.');
        }
    }

    private function authorizeMutation(Transaction $transaction): void
    {
        if (! auth()->user()?->hasPermission('transactions.edit')) {
            abort(403, 'لا تملك صلاحية إدارة مرفقات المعاملة.');
        }

        if ($transaction->isAtFinalStatus()) {
            abort(403, 'لا يمكن تعديل مرفقات معاملة في حالة نهائية.');
        }
    }

    private function ensureAttachmentBelongsToTransaction(Transaction $transaction, TransactionAttachment $attachment): void
    {
        if ($attachment->transaction_id !== $transaction->id) {
            abort(404);
        }
    }
}
