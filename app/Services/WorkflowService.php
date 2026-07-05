<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\WorkflowAction;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /** @return Collection<int, array{action: WorkflowAction, target: TransactionStatus, label: string, button_class: string}> */
    public function availableActions(Transaction $transaction, User $user): Collection
    {
        $actions = collect();
        $status = $transaction->status;

        if (! $status || $transaction->isAtFinalStatus()) {
            return $actions;
        }

        $next = $transaction->nextStatus();
        $previous = $status->previousInWorkflow();

        if ($next && $this->canApprove($transaction, $user)) {
            $workflowAction = $status->is_initial ? WorkflowAction::Submit : WorkflowAction::Approve;

            $actions->push([
                'action' => $workflowAction,
                'target' => $next,
                'label' => $status->is_initial
                    ? __('workflow.label.submit_to', ['status' => $next->name])
                    : __('workflow.label.approve_to', ['status' => $next->name]),
                'button_class' => 'btn-primary',
            ]);
        }

        if ($previous && ! $status->is_initial && $this->canReject($transaction, $user)) {
            $actions->push([
                'action' => WorkflowAction::Reject,
                'target' => $previous,
                'label' => __('workflow.label.reject_to', ['status' => $previous->name]),
                'button_class' => 'btn-danger',
            ]);
        }

        return $actions;
    }

    public function canApprove(Transaction $transaction, User $user): bool
    {
        $status = $transaction->status;
        $next = $transaction->nextStatus();

        if (! $status || ! $next || $transaction->isAtFinalStatus()) {
            return false;
        }

        return $this->canActOnStatus($status, $user);
    }

    public function canReject(Transaction $transaction, User $user): bool
    {
        $status = $transaction->status;
        $previous = $status?->previousInWorkflow();

        if (! $status || ! $previous || $status->is_initial) {
            return false;
        }

        return $this->canActOnStatus($status, $user);
    }

    public function transition(
        Transaction $transaction,
        WorkflowAction $action,
        User $user,
        ?string $notes = null,
    ): bool {
        return match ($action) {
            WorkflowAction::Submit, WorkflowAction::Approve => $this->approve($transaction, $user, $notes),
            WorkflowAction::Reject => $this->reject($transaction, $user, $notes),
            default => false,
        };
    }

    private function approve(Transaction $transaction, User $user, ?string $notes): bool
    {
        if (! $this->canApprove($transaction, $user)) {
            return false;
        }

        $next = $transaction->nextStatus();

        if (! $next) {
            return false;
        }

        $storedAction = $transaction->status?->is_initial
            ? WorkflowAction::Submit
            : WorkflowAction::Approve;

        return $this->applyTransition($transaction, $user, $next, $storedAction, $notes);
    }

    private function reject(Transaction $transaction, User $user, ?string $notes): bool
    {
        if (! $this->canReject($transaction, $user)) {
            return false;
        }

        $previous = $transaction->status?->previousInWorkflow();

        if (! $previous) {
            return false;
        }

        return $this->applyTransition($transaction, $user, $previous, WorkflowAction::Reject, $notes);
    }

    private function canActOnStatus(TransactionStatus $status, User $user): bool
    {
        if ($status->is_initial) {
            return $user->hasPermission(Permission::TransactionsCreate->value);
        }

        if (! $status->required_permission) {
            return false;
        }

        return $user->hasPermission($status->required_permission);
    }

    private function applyTransition(
        Transaction $transaction,
        User $user,
        TransactionStatus $target,
        WorkflowAction $action,
        ?string $notes,
    ): bool {
        return DB::transaction(function () use ($transaction, $user, $target, $action, $notes) {
            $locked = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            $locked->load('status');

            $allowed = match ($action) {
                WorkflowAction::Approve, WorkflowAction::Submit => $this->canApprove($locked, $user),
                WorkflowAction::Reject => $this->canReject($locked, $user),
                default => false,
            };

            if (! $allowed || $locked->isAtFinalStatus()) {
                return false;
            }

            $fromStatusId = $locked->transaction_status_id;

            $locked->update(['transaction_status_id' => $target->id]);

            $locked->statusHistories()->create([
                'from_status_id' => $fromStatusId,
                'to_status_id' => $target->id,
                'changed_by' => $user->id,
                'action' => $action->value,
                'notes' => $notes,
            ]);

            $transaction->setRelation('status', $target);
            $transaction->transaction_status_id = $target->id;

            return true;
        });
    }
}
