<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class BaseFilter
{
    protected array $filterTypes = [
        'ilike', 'exact', 'range', 'date', 'custom', 'whereAny', 'whereAll'
    ];

    /**
     * Apply filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function apply(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter => $value) {
            if (method_exists($this, $filter)) {
                $this->$filter($query, $value);
            } else {
                $type = $this->getFilterType($filter);
                $this->applyFilterByType($query, $filter, $value, $type);
            }
        }

        return $query;
    }

    /**
     * Define filter types dynamically.
     *
     * @param string $filter
     * @return string|null
     */
    protected function getFilterType(string $filter): ?string
    {
        return null;
    }

    /**
     * Apply a filter based on its type.
     *
     * @param Builder $query
     * @param string $filter
     * @param mixed $value
     * @param string $type
     * @return void
     */
    protected function applyFilterByType(Builder $query, string $filter, $value, ?string $type): void
    {
        if($type === null) {
            return;
        }

        switch ($type) {
            case 'ilike':
                if($value)
                    $query->where($filter, 'ILIKE', "%{$value}%");
                break;
            case 'exact':
                if($value)
                    $query->where($filter, $value);
                break;
            case 'range':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($filter, $value);
                }
                break;
            case 'date':
                if($value)
                    $query->whereDate($filter, $value);
                break;
            case 'whereAny':
                if($value)
                    $query->whereAny(explode(',', $filter), 'ILIKE', "%{$value}%");
                break;
            case 'whereAll':
                if($value)
                    $query->whereAll(explode(',', $filter), 'ILIKE', "%{$value}%");
                break;
            case 'custom':
                if (method_exists($this, "filter" . ucfirst($filter))) {
                    $this->{"filter" . ucfirst($filter)}($query, $value);
                }
                break;
            default:
                break;
        }
    }
}
