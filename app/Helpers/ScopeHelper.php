<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class ScopeHelper
{
    const LIMITED_ROLES = ['manager', 'supervisor'];

    public static function isLimitedRole(): bool
    {
        return in_array(auth()->user()?->role, self::LIMITED_ROLES);
    }

    public static function getDepartmentId(): ?int
    {
        return auth()->user()?->employee?->department_id;
    }

    /**
     * Terapkan filter departemen ke query jika role terbatas.
     * $employeeRelation = nama relasi ke employee di model yang di-query.
     */
    public static function applyDepartmentScope(Builder $query, string $employeeRelation = 'employee'): Builder
    {
        if (! self::isLimitedRole()) {
            return $query;
        }

        $deptIds = self::getDepartmentIds();

        if (empty($deptIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas($employeeRelation, function ($q) use ($deptIds) {
            $q->whereIn('department_id', $deptIds);
        });
    }

    /**
     * Ambil semua department ID: department sendiri + semua turunannya (rekursif)
     */
    public static function getDepartmentIds(): array
    {
        $deptId = self::getDepartmentId();
        if (! $deptId) {
            return [];
        }

        return self::collectChildIds($deptId);
    }

    private static function collectChildIds(int $deptId): array
    {
        $ids = [$deptId];

        $children = \App\Models\Department::where('parent_id', $deptId)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $ids = array_merge($ids, self::collectChildIds($childId));
        }

        return array_unique($ids);
    }
}
