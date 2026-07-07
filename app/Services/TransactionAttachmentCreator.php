<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class TransactionAttachmentCreator
{
    public function __construct(
        private ReferenceNumberService $referenceNumbers,
    ) {}

    /**
     * @param  array{
     *     title?: string|null,
     *     reference_number?: string|null,
     *     reference_year?: int|string|null,
     *     reference_month?: int|string|null,
     *     original_document_number?: string|null,
     *     use_operational?: bool|string|int|null,
     * }  $meta
     */
    public function create(
        Transaction $transaction,
        UploadedFile $file,
        array $meta,
        User $user,
        ?int $sortOrder = null,
    ): TransactionAttachment {
        $transaction->loadMissing(['department', 'transactionType']);

        if ($sortOrder === null) {
            $sortOrder = ((int) $transaction->attachments()->max('sort_order')) + 1;
        }

        $path = $file->store('transaction-attachments/'.$transaction->id, 'local');

        $referenceData = $this->referenceNumbers->resolveForAttachment(
            [
                'reference_number' => $meta['reference_number'] ?? null,
                'reference_year' => $meta['reference_year'] ?? null,
                'reference_month' => $meta['reference_month'] ?? null,
                'original_document_number' => $meta['original_document_number'] ?? null,
                'use_operational' => $meta['use_operational'] ?? false,
            ],
            $transaction->department,
            $transaction->transactionType,
            $user,
        );

        return $transaction->attachments()->create([
            'title' => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
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
            'uploaded_by' => $user->id,
            'sort_order' => $sortOrder,
        ]);
    }
}
