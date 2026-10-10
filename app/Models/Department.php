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

    protected static function booted(): void
    {
        static::deleting(function (Department $department): ?bool {
            if (! $department->isDeletable()) {
                return false;
            }

            return null;
        });
    }

    public function isDeletable(): bool
    {
        return $this->deletionBlockers() === [];
    }

    /**
     * Reasons this unit cannot be deleted, with the matching record counts.
     *
     * @return list<array{key: string, count: int}>
     */
    public function deletionBlockers(): array
    {
        $checks = [
            'children' => $this->relatedCount('children', fn () => $this->children()->count()),
            'folders' => $this->relatedCount('folders', fn () => $this->folders()->count()),
            'transactions' => $this->relatedCount('transactions', fn () => $this->transactions()->count()),
            'employees' => $this->relatedCount('users', fn () => $this->users()->count()),
            'documents' => $this->relatedCount('documents', fn () => $this->documents()->withTrashed()->count(), false),
        ];

        $blockers = [];

        foreach ($checks as $key => $count) {
            if ($count > 0) {
                $blockers[] = ['key' => $key, 'count' => $count];
            }
        }

        return $blockers;
    }

    public function deletionBlockedMessage(): ?string
    {
        $blockers = $this->deletionBlockers();

        if ($blockers === []) {
            return null;
        }

        $reasons = array_map(
            fn (array $blocker) => __('messages.organization.cannot_delete_reason.'.$blocker['key'], [
                'count' => $blocker['count'],
            ]),
            $blockers,
        );

        return __('messages.organization.cannot_delete_because', [
            'reasons' => implode(__('messages.organization.cannot_delete_reason.separator'), $reasons),
        ]);
    }

    private function relatedCount(string $relation, callable $query, bool $countLoadedRelation = true): int
    {
        $attribute = $relation.'_count';

        if (array_key_exists($attribute, $this->getAttributes())) {
            return (int) $this->getAttribute($attribute);
        }

        if ($countLoadedRelation && $this->relationLoaded($relation)) {
            return $this->getRelation($relation)->count();
        }

        return (int) $query();
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public static function tree(): Collection
    {
        return static::query()
            ->with(['head', 'children' => fn ($query) => static::nestedChildrenQuery($query)])
            ->tap(fn ($query) => static::applyTreeCounts($query))
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    /** @param \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder $query */
    private static function nestedChildrenQuery($query): void
    {
        $query
            ->with(['head', 'children' => fn ($childQuery) => static::nestedChildrenQuery($childQuery)])
            ->tap(fn ($childQuery) => static::applyTreeCounts($childQuery))
            ->orderBy('name');
    }

    /** @param \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder $query */
    private static function applyTreeCounts($query): void
    {
        $query->withCount([
            'users',
            'folders',
            'transactions',
            'documents' => fn ($documents) => $documents->withTrashed(),
        ]);
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
