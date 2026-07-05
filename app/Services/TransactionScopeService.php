<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TransactionScopeService
{
    public function hasUnrestrictedAccess(User $user): bool
    {
        return $user->hasPermission('transactions.view-all');
    }

    public function canViewTransaction(User $user, Transaction $transaction): bool
    {
        if ($this->hasUnrestrictedAccess($user)) {
            return true;
        }

        $transaction->loadMissing('status');

        foreach ($this->visibilityRules($user) as $rule) {
            if ($this->transactionMatchesRule($transaction, $rule)) {
                return true;
            }
        }

        return false;
    }

    /** @param Builder<Transaction> $query */
    public function applyScopeToQuery(Builder $query, User $user): void
    {
        if ($this->hasUnrestrictedAccess($user)) {
            return;
        }

        $rules = $this->visibilityRules($user);

        if ($rules === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->where(function (Builder $outer) use ($rules) {
            foreach ($rules as $rule) {
                $outer->orWhere(fn (Builder $inner) => $this->applyRuleToQuery($inner, $rule));
            }
        });
    }

    /** @return list<int> */
    public function visibleStatusIds(User $user): array
    {
        if ($this->hasUnrestrictedAccess($user)) {
            return TransactionStatus::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        }

        if ($user->hasPermission('transactions.create')) {
            return TransactionStatus::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('id')
                ->all();
        }

        return $this->workflowStatusesForUser($user)
            ->pluck('id')
            ->all();
    }

    /** @return list<array{type: string, status_id?: int, scope?: string, department_ids?: list<int>|null}> */
    private function visibilityRules(User $user): array
    {
        $rules = [];

        if ($user->hasPermission('transactions.create')) {
            $departmentIds = $user->orgScopeDepartmentIds();

            if ($departmentIds === null || $departmentIds !== []) {
                $rules[] = [
                    'type' => 'unit',
                    'department_ids' => $departmentIds,
                ];
            }
        }

        foreach ($this->workflowStatusesForUser($user) as $status) {
            $departmentIds = $status->isGlobalScope()
                ? null
                : $user->orgScopeDepartmentIds();

            if (! $status->isGlobalScope() && $departmentIds === []) {
                continue;
            }

            $rules[] = [
                'type' => 'workflow',
                'status_id' => $status->id,
                'scope' => $status->visibility_scope,
                'department_ids' => $departmentIds,
            ];
        }

        return $rules;
    }

    /** @param array{type: string, status_id?: int, scope?: string, department_ids?: list<int>|null} $rule */
    private function transactionMatchesRule(Transaction $transaction, array $rule): bool
    {
        if ($rule['type'] === 'unit') {
            $departmentIds = $rule['department_ids'];

            if ($departmentIds === null) {
                return true;
            }

            return in_array((int) $transaction->department_id, array_map(intval(...), $departmentIds), true);
        }

        if ((int) $transaction->transaction_status_id !== (int) $rule['status_id']) {
            return false;
        }

        if (($rule['scope'] ?? 'unit') === 'global') {
            return true;
        }

        $departmentIds = $rule['department_ids'];

        if ($departmentIds === null) {
            return true;
        }

        if ($departmentIds === []) {
            return false;
        }

        return in_array((int) $transaction->department_id, array_map(intval(...), $departmentIds), true);
    }

    /** @param array{type: string, status_id?: int, scope?: string, department_ids?: list<int>|null} $rule */
    private function applyRuleToQuery(Builder $query, array $rule): void
    {
        if ($rule['type'] === 'unit') {
            $departmentIds = $rule['department_ids'];

            if ($departmentIds !== null) {
                $query->whereIn('department_id', $departmentIds);
            }

            return;
        }

        $query->where('transaction_status_id', $rule['status_id']);

        if (($rule['scope'] ?? 'unit') !== 'global' && $rule['department_ids'] !== null) {
            $query->whereIn('department_id', $rule['department_ids']);
        }
    }

    /** @return \Illuminate\Support\Collection<int, TransactionStatus> */
    private function workflowStatusesForUser(User $user)
    {
        return TransactionStatus::query()
            ->where('is_active', true)
            ->where('is_initial', false)
            ->whereNotNull('required_permission')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (TransactionStatus $status) => $user->hasPermission($status->required_permission));
    }
}
