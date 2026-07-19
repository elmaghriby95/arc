<?php

namespace App\Models;

use App\Enums\LendingRequestAction;
use App\Enums\LendingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LendingRequestHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'lending_request_id',
        'action',
        'from_status',
        'to_status',
        'performed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => LendingRequestAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function lendingRequest(): BelongsTo
    {
        return $this->belongsTo(LendingRequest::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function actionLabel(): string
    {
        if ($this->action instanceof LendingRequestAction) {
            return $this->action->label();
        }

        return LendingRequestAction::tryFrom((string) $this->action)?->label()
            ?? (string) $this->action;
    }

    public function fromStatusEnum(): ?LendingRequestStatus
    {
        return $this->from_status
            ? LendingRequestStatus::tryFrom((string) $this->from_status)
            : null;
    }

    public function toStatusEnum(): ?LendingRequestStatus
    {
        return $this->to_status
            ? LendingRequestStatus::tryFrom((string) $this->to_status)
            : null;
    }

    public function fromStatusLabel(): string
    {
        return $this->fromStatusEnum()?->label() ?? '—';
    }

    public function toStatusLabel(): string
    {
        return $this->toStatusEnum()?->label() ?? '—';
    }
}
