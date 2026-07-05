<?php

namespace App\Models;

use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TransactionStatus extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'sort_order',
        'required_permission',
        'color',
        'is_initial',
        'is_final',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public static function initial(): ?self
    {
        return static::where('is_active', true)
            ->where('is_initial', true)
            ->first()
            ?? static::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, self> */
    public static function workflowSequence()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function nextInWorkflow(): ?self
    {
        return static::where('is_active', true)
            ->where(function ($query) {
                $query->where('sort_order', '>', $this->sort_order)
                    ->orWhere(function ($q) {
                        $q->where('sort_order', $this->sort_order)
                            ->where('id', '>', $this->id);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function previousInWorkflow(): ?self
    {
        return static::where('is_active', true)
            ->where(function ($query) {
                $query->where('sort_order', '<', $this->sort_order)
                    ->orWhere(function ($q) {
                        $q->where('sort_order', $this->sort_order)
                            ->where('id', '<', $this->id);
                    });
            })
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->first();
    }

    public function workflowPermissionKey(): string
    {
        return 'transactions.workflow.'.Str::lower($this->code);
    }

    public function workflowPermissionLabel(): string
    {
        return 'انتقال — '.$this->name;
    }

    /** @return list<PermissionOption> */
    public static function workflowPermissionOptions(): array
    {
        return static::query()
            ->where('is_initial', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (self $status) => new \App\Support\PermissionOption(
                $status->workflowPermissionKey(),
                $status->workflowPermissionLabel(),
            ))
            ->all();
    }

    /** @return list<string> */
    public static function workflowPermissionKeys(): array
    {
        return static::query()
            ->where('is_initial', false)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (self $status) => $status->workflowPermissionKey())
            ->all();
    }

    public static function findByWorkflowPermission(string $permissionKey): ?self
    {
        $status = static::query()->where('required_permission', $permissionKey)->first();

        if ($status) {
            return $status;
        }

        return static::query()
            ->get()
            ->first(fn (self $candidate) => $candidate->workflowPermissionKey() === $permissionKey);
    }

    public function permissionLabel(): ?string
    {
        if ($this->is_initial) {
            return null;
        }

        if (! $this->required_permission) {
            return null;
        }

        return PermissionRegistry::labelFor($this->required_permission);
    }
}
