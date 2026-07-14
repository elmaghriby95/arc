<?php

namespace App\Models;

use App\Enums\LendingRequestAction;
use App\Enums\TransactionLendingStatus;
use App\Enums\WorkflowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Unified workflow + lending activity for the transaction status history table.
     *
     * @return Collection<int, object{
     *     kind: string,
     *     action: ?string,
     *     action_label: string,
     *     from_label: string,
     *     to_workflow_status: ?TransactionStatus,
     *     to_lending_status: ?\App\Enums\LendingRequestStatus,
     *     by: string,
     *     notes: ?string,
     *     created_at: ?\Illuminate\Support\Carbon
     * }>
     */
    public function statusTimeline(): Collection
    {
        $workflow = $this->statusHistories->map(function (TransactionStatusHistory $history) {
            $action = $history->action;

            return (object) [
                'kind' => 'workflow',
                'action' => $action,
                'action_label' => WorkflowAction::tryFrom((string) ($action ?? ''))?->label()
                    ?? ($action ?: '—'),
                'from_label' => $history->fromStatus?->name ?? '—',
                'to_workflow_status' => $history->toStatus,
                'to_lending_status' => null,
                'by' => $history->changedBy?->name ?? '—',
                'notes' => $history->notes,
                'created_at' => $history->created_at,
            ];
        });

        $lending = $this->lendingRequests
            ->flatMap(fn (LendingRequest $request) => $request->histories)
            ->map(function (LendingRequestHistory $history) {
                $action = $history->action instanceof LendingRequestAction
                    ? $history->action->value
                    : (string) $history->action;

                return (object) [
                    'kind' => 'lending',
                    'action' => $action,
                    'action_label' => $history->actionLabel(),
                    'from_label' => $history->fromStatusLabel(),
                    'to_workflow_status' => null,
                    'to_lending_status' => $history->toStatusEnum(),
                    'by' => $history->performer?->name ?? '—',
                    'notes' => $history->notes,
                    'created_at' => $history->created_at,
                ];
            });

        return $workflow
            ->concat($lending)
            ->sortByDesc(fn (object $entry) => $entry->created_at?->getTimestamp() ?? 0)
            ->values();
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
