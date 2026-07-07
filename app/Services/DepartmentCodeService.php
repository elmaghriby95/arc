<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\Collection;

class DepartmentCodeService
{
    public function generateForParent(?int $parentId): string
    {
        $parent = $parentId ? Department::query()->find($parentId) : null;
        $siblingCodes = Department::query()
            ->where('parent_id', $parentId)
            ->pluck('code');

        if ($parent === null) {
            $max = 0;
            foreach ($siblingCodes as $code) {
                if (preg_match('/^\d+$/', (string) $code)) {
                    $max = max($max, (int) $code);
                }
            }

            return (string) ($max + 1);
        }

        $parentCode = (string) $parent->code;
        $prefix = $parentCode.'-';
        $max = 0;

        foreach ($siblingCodes as $code) {
            $code = (string) $code;

            if (! str_starts_with($code, $prefix)) {
                continue;
            }

            $segment = (int) explode('-', substr($code, strlen($prefix)))[0];
            $max = max($max, $segment);
        }

        return $parentCode.'-'.($max + 1);
    }

    public function recalculateAll(): void
    {
        $departments = Department::query()->orderBy('id')->get(['id', 'parent_id']);
        $childrenMap = $departments->groupBy(fn (Department $department) => $department->parent_id ?? 'root');

        $assign = function (?int $parentId, ?string $parentCode) use (&$assign, $childrenMap): void {
            $children = $childrenMap->get($parentId ?? 'root', collect());
            $index = 1;

            /** @var Department $child */
            foreach ($children as $child) {
                $code = $parentCode === null ? (string) $index : $parentCode.'-'.$index;
                Department::query()->whereKey($child->id)->update(['code' => $code]);
                $assign($child->id, $code);
                $index++;
            }
        };

        $assign(null, null);
    }

    /** @return Collection<int, Department> */
    public function orderedRoots(): Collection
    {
        return Department::query()
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get();
    }
}
