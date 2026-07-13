<?php

namespace App\Services;

use App\Models\DocumentAccessAudit;
use App\Models\TransactionAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentAccessAuditService
{
    public function createPending(
        User $user,
        TransactionAttachment $attachment,
        string $actionType,
        Request $request,
    ): DocumentAccessAudit {
        return DocumentAccessAudit::create([
            'transaction_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'attachment_id' => $attachment->id,
            'document_version' => $this->resolveVersion($attachment),
            'action_type' => $actionType,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'status' => 'success',
            'watermark_applied' => false,
        ]);
    }

    public function markSuccess(DocumentAccessAudit $audit, bool $watermarkApplied): void
    {
        $audit->update([
            'status' => 'success',
            'watermark_applied' => $watermarkApplied,
            'failure_reason' => null,
        ]);
    }

    public function markFailure(DocumentAccessAudit $audit, string $reason): void
    {
        $audit->update([
            'status' => 'failure',
            'watermark_applied' => false,
            'failure_reason' => Str::limit($reason, 250),
        ]);
    }

    private function resolveVersion(TransactionAttachment $attachment): string
    {
        if ($attachment->document_id) {
            $version = $attachment->document?->versions()?->max('version_number');

            if ($version) {
                return (string) $version;
            }
        }

        return '1';
    }
}
