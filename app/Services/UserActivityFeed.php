<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UserActivityFeed
{
    /** @return Collection<int, array<string, mixed>> */
    public function forUser(User $user, int $limit = 50): Collection
    {
        $activities = collect();

        AuditLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (AuditLog $log) use ($activities) {
                $activities->push($this->fromAuditLog($log));
            });

        Transaction::query()
            ->where('created_by', $user->id)
            ->latest()
            ->limit(20)
            ->get(['id', 'reference_number', 'title', 'created_at'])
            ->each(function (Transaction $transaction) use ($activities) {
                $activities->push([
                    'type' => 'transaction',
                    'action' => 'created',
                    'label' => __('profile.activity.transaction_created'),
                    'description' => $transaction->title,
                    'meta' => $transaction->reference_number,
                    'url' => route('transactions.show', $transaction),
                    'icon' => 'transaction',
                    'occurred_at' => $transaction->created_at,
                ]);
            });

        Document::query()
            ->where('uploaded_by', $user->id)
            ->latest()
            ->limit(20)
            ->get(['id', 'reference_number', 'title', 'created_at'])
            ->each(function (Document $document) use ($activities) {
                $activities->push([
                    'type' => 'document',
                    'action' => 'uploaded',
                    'label' => __('profile.activity.document_uploaded'),
                    'description' => $document->title,
                    'meta' => $document->reference_number,
                    'url' => route('documents.index', ['search' => $document->reference_number]),
                    'icon' => 'document',
                    'occurred_at' => $document->created_at,
                ]);
            });

        TransactionStatusHistory::query()
            ->where('changed_by', $user->id)
            ->latest('created_at')
            ->limit(20)
            ->with(['transaction:id,reference_number,title', 'toStatus:id,name'])
            ->get()
            ->each(function (TransactionStatusHistory $history) use ($activities) {
                if (! $history->transaction) {
                    return;
                }

                $activities->push([
                    'type' => 'workflow',
                    'action' => $history->action ?? 'status_change',
                    'label' => __('profile.activity.status_changed'),
                    'description' => $history->transaction->title,
                    'meta' => $history->toStatus?->name,
                    'url' => route('transactions.show', $history->transaction),
                    'icon' => 'workflow',
                    'occurred_at' => $history->created_at,
                ]);
            });

        return $activities
            ->filter(fn (array $item) => $item['occurred_at'] instanceof Carbon)
            ->sortByDesc(fn (array $item) => $item['occurred_at']->timestamp)
            ->take($limit)
            ->values();
    }

    /** @return array<string, int> */
    public function statsFor(User $user): array
    {
        return [
            'documents' => Document::where('uploaded_by', $user->id)->count(),
            'transactions' => Transaction::where('created_by', $user->id)->count(),
            'workflow_actions' => TransactionStatusHistory::where('changed_by', $user->id)->count(),
            'audit_entries' => AuditLog::where('user_id', $user->id)->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function fromAuditLog(AuditLog $log): array
    {
        $labels = [
            'login' => __('profile.activity.login'),
            'logout' => __('profile.activity.logout'),
            'profile.updated' => __('profile.activity.profile_updated'),
            'profile.avatar_updated' => __('profile.activity.avatar_updated'),
            'password.changed' => __('profile.activity.password_changed'),
        ];

        $icons = [
            'login' => 'login',
            'logout' => 'logout',
            'profile.updated' => 'profile',
            'profile.avatar_updated' => 'avatar',
            'password.changed' => 'security',
        ];

        return [
            'type' => 'audit',
            'action' => $log->action,
            'label' => $labels[$log->action] ?? $log->action,
            'description' => $this->auditDescription($log),
            'meta' => $log->ip_address,
            'url' => null,
            'icon' => $icons[$log->action] ?? 'audit',
            'occurred_at' => $log->created_at,
        ];
    }

    private function auditDescription(AuditLog $log): string
    {
        return match ($log->action) {
            'login' => __('profile.activity.login_desc'),
            'logout' => __('profile.activity.logout_desc'),
            'profile.updated' => __('profile.activity.profile_updated_desc'),
            'profile.avatar_updated' => __('profile.activity.avatar_updated_desc'),
            'password.changed' => __('profile.activity.password_changed_desc'),
            default => $log->action,
        };
    }
}
