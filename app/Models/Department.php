<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'unit_label',
        'code',
        'description',
        'is_active',
        'parent_id',
        'head_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')->orderBy('name');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public static function tree(): Collection
    {
        return static::query()
            ->with(['head', 'children' => fn ($query) => static::nestedChildrenQuery($query)])
            ->withCount('users')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    /** @param \Illuminate\Database\Eloquent\Relations\HasMany $query */
    private static function nestedChildrenQuery($query): void
    {
        $query
            ->with(['head', 'children' => fn ($childQuery) => static::nestedChildrenQuery($childQuery)])
            ->withCount('users')
            ->orderBy('name');
    }

    public function breadcrumb(): string
    {
        return static::breadcrumbMap()[$this->id] ?? $this->displayName();
    }

    public function displayName(): string
    {
        return ($this->unit_label ? "{$this->unit_label}: " : '').$this->name;
    }

    /** @return array<int, string> */
    public static function breadcrumbMap(): array
    {
        $departments = static::query()->orderBy('name')->get()->keyBy('id');
        $map = [];

        foreach ($departments as $department) {
            $parts = [];
            $current = $department;

            while ($current) {
                array_unshift($parts, $current->displayName());
                $current = $current->parent_id ? $departments->get($current->parent_id) : null;
            }

            $map[$department->id] = implode(' ← ', $parts);
        }

        return $map;
    }

    /** @return list<int> */
    public static function descendantIdsIncludingSelf(int $rootId): array
    {
        $childrenMap = static::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [];
        $queue = [$rootId];

        while ($queue !== []) {
            $id = array_shift($queue);
            $ids[] = $id;

            foreach ($childrenMap->get($id, []) as $child) {
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /** @return list<array{id: int, label: string, depth: int}> */
    public static function optionsForSelect(): array
    {
        $breadcrumbs = static::breadcrumbMap();
        $options = [];

        foreach (static::tree() as $root) {
            static::collectSelectOptions($root, 0, $options, $breadcrumbs);
        }

        return $options;
    }

    /** @param list<array{id: int, label: string, depth: int}> $options */
    /** @param array<int, string> $breadcrumbs */
    private static function collectSelectOptions(self $node, int $depth, array &$options, array $breadcrumbs): void
    {
        $options[] = [
            'id' => $node->id,
            'label' => $breadcrumbs[$node->id] ?? $node->displayName(),
            'depth' => $depth,
        ];

        foreach ($node->children as $child) {
            static::collectSelectOptions($child, $depth + 1, $options, $breadcrumbs);
        }
    }
}
