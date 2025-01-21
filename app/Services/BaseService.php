<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected string $modelClass;
    protected ?string $cachePrefix = null;

    public function __construct()
    {
        if (!isset($this->modelClass)) {
            throw new \Exception("Property 'modelClass' must be defined in the service.");
        }

        if (!$this->cachePrefix) {
            $this->cachePrefix = strtolower(class_basename($this->modelClass));
        }
    }

    /**
     * Create a new resource.
     *
     * @param  array  $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            return $this->getModelInstance()->create($data);
        }, 3);
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @param  array  $data
     * @return Model
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->getById($id);
        $model->update($data);

        return $model;
    }

    /**
     * Get a new query builder instance.
     *
     * @return Builder
     */
    public function getQueryBuilder(): Builder
    {
        return $this->getModelInstance()->newQuery();
    }

    /**
     * Delete the specified resource.
     *
     * @param  int  $id
     * @return void
     */
    public function delete(int $id): void
    {
        $model = $this->getById($id);
        DB::transaction(function () use ($model) {
            $model->delete();
        });
    }

    /**
     * Get the specified resource by ID.
     *
     * @param  int  $id
     * @return Model
     */
    public function getById(int $id): Model
    {
        return Cache::remember("{$this->cachePrefix}_{$id}", 60, function () use ($id) {
            return $this->getModelInstance()->findOrFail($id);
        });
    }

    /**
     * Get all resources with pagination and optional filtering.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(Request $request)
    {
        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        $query = $this->getModelInstance()->applyFilters($request);

        $validSortColumns = $this->getValidSortColumns();

        if (in_array($sortBy, $validSortColumns)) {
            $query->orderBy($sortBy, $orderBy);
        } else {
            $query->orderBy('id', 'asc');
        }

        return $query->paginate($itemsPerPage, ['*'], 'page', $page);
    }

    /**
     * Get a new instance of the model.
     *
     * @return Model
     */
    public function getModelInstance(): Model
    {
        return new $this->modelClass;
    }

    /**
     * Get valid columns for sorting.
     *
     * @return array
     */
    protected function getValidSortColumns(): array
    {
        return ['id'];
    }
}
