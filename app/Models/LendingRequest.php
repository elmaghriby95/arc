<?php

namespace App\Models;

use App\Enums\LendingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LendingRequest extends Model
{
    protected $fillable = [
        'transaction_id',
        'requested_by',
        'status',
        'purpose',
        'due_date',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'handed_over_by',
        'handed_over_at',
        'handover_notes',
        'returned_by',
        'returned_at',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_id' => 'integer',
            'requested_by' => 'integer',
            'reviewed_by' => 'integer',
            'handed_over_by' => 'integer',
            'returned_by' => 'integer',
            'status' => LendingRequestStatus::class,
            'due_date' => 'date',
            'reviewed_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function handoverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function returnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LendingRequestHistory::class)->orderByDesc('created_at');
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
