<?php

namespace App\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class ProjectFilter extends BaseFilter
{
    /**
     * Override filter type definitions.
     *
     * @param string $filter
     * @return string|null
     */
    protected function getFilterType(string $filter): ?string
    {
        $types = [
            'q' => 'whereAny',
            'is_active' => 'exact',
        ];

        return $types[$filter] ?? null;
    }

    protected function applyFilterByType(Builder $query, string $filter, $value, ?string $type): void
    {
        if ($type === 'whereAny' && $filter === 'q') {
            $query->whereAny(['name'], 'ILIKE', "%{$value}%");
        } else {
            parent::applyFilterByType($query, $filter, $value, $type);
        }
    }

    /**
     * Example of a custom filter.
     *
     * @param Builder $query
     * @param mixed $value
     * @return void
     */
    protected function filterCustomLogic(Builder $query, $value): void
    {
        // Define your custom filtering logic
    }
}
