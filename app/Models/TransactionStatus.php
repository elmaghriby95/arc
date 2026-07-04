<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function permissionLabel(): ?string
    {
        if (! $this->required_permission) {
            return null;
        }

        foreach (\App\Enums\Permission::cases() as $permission) {
            if ($permission->value === $this->required_permission) {
                return $permission->label();
            }
        }

        return $this->required_permission;
    }
}
