<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessAudit extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'attachment_id',
        'document_version',
        'action_type',
        'ip_address',
        'user_agent',
        'session_id',
        'status',
        'failure_reason',
        'watermark_applied',
    ];

    protected function casts(): array
    {
        return [
            'watermark_applied' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(TransactionAttachment::class, 'attachment_id');
    }
}
