<?php

namespace App\Models;

use App\Enums\LendingRequestAction;
use App\Enums\LendingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LendingRequestHistory extends Model
{
    public $timestamps = false;

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
            'from_status' => LendingRequestStatus::class,
            'to_status' => LendingRequestStatus::class,
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
}
