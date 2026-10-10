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
        'cabinet_number',
        'row_number',
        'box_number',
        'description',
        'color',
        'sort_order',
        'is_active',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Folder $folder): ?bool {
            if ($folder->transactions()->exists()) {
                return false;
            }

            return null;
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function locationLabel(): string
    {
        return implode(' · ', array_filter([
            $this->cabinet_number ? __('settings.folders.cabinet_badge', ['number' => $this->cabinet_number]) : null,
            $this->row_number ? __('settings.folders.row_badge', ['number' => $this->row_number]) : null,
            $this->box_number ? __('settings.folders.box_badge', ['number' => $this->box_number]) : null,
        ]));
    }

    /** @param list<int>|null $departmentIds null = unrestricted (admin) */
    public static function scopedTree(?array $departmentIds, bool $activeOnly = false, bool $withTransactionCount = false, bool $excludeClosed = false): Collection
    {
        $query = static::scopedQuery($departmentIds)->with('department');

        if ($withTransactionCount) {
            $query->withCount('transactions');
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $folders = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($excludeClosed) {
            $folders = static::withoutClosedFolders($folders);
        }

        return static::buildTreeFromFlat($folders);
    }

    /**
     * Closed folders are omitted. Their open descendants stay available under the nearest open ancestor.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $folders
     */
    private static function withoutClosedFolders($folders)
    {
        $byId = $folders->keyBy(fn (self $folder) => (int) $folder->id);

        $nearestOpenParentId = function (?int $parentId, array $seen = []) use (&$nearestOpenParentId, $byId): ?int {
            if ($parentId === null || isset($seen[$parentId]) || ! $byId->has($parentId)) {
                return null;
            }

            $seen[$parentId] = true;
            $parent = $byId->get($parentId);

            if (! $parent->is_closed) {
                return (int) $parent->id;
            }

            return $nearestOpenParentId(
                $parent->parent_id === null ? null : (int) $parent->parent_id,
                $seen,
            );
        };

        return $folders
            ->reject(fn (self $folder) => (bool) $folder->is_closed)
            ->map(function (self $folder) use ($nearestOpenParentId) {
                $currentParentId = $folder->parent_id === null ? null : (int) $folder->parent_id;
                $parentId = $nearestOpenParentId($currentParentId);

                if ($parentId === $currentParentId) {
                    return $folder;
                }

                $copy = clone $folder;
                $copy->parent_id = $parentId;
                $copy->syncOriginal();

                return $copy;
            })
            ->values();
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
