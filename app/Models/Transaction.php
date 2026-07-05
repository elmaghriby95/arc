<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'reference_number',
        'title',
        'description',
        'transaction_type_id',
        'department_id',
        'folder_id',
        'transaction_status_id',
        'created_by',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
        ];
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TransactionStatus::class, 'transaction_status_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TransactionStatusHistory::class)->orderByDesc('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function nextStatus(): ?TransactionStatus
    {
        return $this->status?->nextInWorkflow();
    }

    public function isAtFinalStatus(): bool
    {
        return (bool) $this->status?->is_final;
    }

    public function isAtInitialStatus(): bool
    {
        return (bool) $this->status?->is_initial;
    }

}
