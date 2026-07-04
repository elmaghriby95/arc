<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'from_status_id',
        'to_status_id',
        'changed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'to_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
