<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    protected $fillable = [
        'parent_id',
        'department_id',
        'name',
        'description',
        'color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** @param list<int>|null $departmentIds null = unrestricted (admin) */
    public static function scopedTree(?array $departmentIds): Collection
    {
        $folders = static::scopedQuery($departmentIds)
            ->with('department')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return static::buildTreeFromFlat($folders);
    }

    /** @param list<int>|null $departmentIds */
    public static function scopedQuery(?array $departmentIds): Builder
    {
        $query = static::query();

        if ($departmentIds !== null) {
            $query->whereIn('department_id', $departmentIds);
        }

        return $query;
    }

    public static function tree(): Collection
    {
        return static::scopedTree(null);
    }

    /** @param \Illuminate\Support\Collection<int, self> $folders */
    private static function buildTreeFromFlat($folders): Collection
    {
        $accessibleIds = $folders->pluck('id')->flip();
        $byParent = $folders->groupBy(fn (self $folder) => $folder->parent_id ?? 'none');

        $attachChildren = function (self $folder) use (&$attachChildren, $byParent): self {
            $children = ($byParent->get($folder->id) ?? collect())->map($attachChildren);
            $folder->setRelation('children', $children);

            return $folder;
        };

        $roots = $folders->filter(
            fn (self $folder) => $folder->parent_id === null || ! isset($accessibleIds[$folder->parent_id])
        );

        return $roots->map($attachChildren)->values();
    }
}
