<?php

namespace App\Models;

use App\Enums\TransactionLendingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'reference_number',
        'archival_reference',
        'title',
        'description',
        'transaction_type_id',
        'department_id',
        'folder_id',
        'transaction_status_id',
        'created_by',
        'transaction_date',
        'notes',
        'lending_status',
        'active_lending_request_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'lending_status' => TransactionLendingStatus::class,
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

    public function lendingRequests(): HasMany
    {
        return $this->hasMany(LendingRequest::class)->orderByDesc('created_at');
    }

    public function activeLendingRequest(): BelongsTo
    {
        return $this->belongsTo(LendingRequest::class, 'active_lending_request_id');
    }

    public function isOnLoan(): bool
    {
        return $this->lending_status === TransactionLendingStatus::OnLoan;
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

    public function canBeEdited(): bool
    {
        return $this->isAtInitialStatus();
    }

}
