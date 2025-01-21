<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait Filterable
{
    /**
     * Scope a query to apply filters using a filter class.
     *
     * @param Builder $query
     * @param array|Request $filters
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, $filters): Builder
    {
        if ($filters instanceof Request) {
            $filters = $filters->all();
        }

        $filterClass = $this->getFilterClass();

        if (!$filterClass || !class_exists($filterClass)) {
            throw new \Exception("Filter class {$filterClass} not found.");
        }

        return (new $filterClass())->apply($query, $filters);
    }

    /**
     * Get the filter class associated with the model.
     *
     * @return string|null
     */
    protected function getFilterClass(): ?string
    {
        if (property_exists($this, 'filterClass') && $this->filterClass) {
            return $this->filterClass;
        }

        $modelName = class_basename($this);
        $filterClass = "App\\Filters\\{$modelName}Filter";

        return class_exists($filterClass) ? $filterClass : null;
    }
}
