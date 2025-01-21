<?php

namespace App\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class TimeEntryFilter extends BaseFilter
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
            // Define your filter types
        ];

        return $types[$filter] ?? null;
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