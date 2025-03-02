<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityService
{
    public function getContainerActivities(Container $container, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Activity::query()
            ->where('container_id', $container->id)
            ->when(isset($filters['subject_type']), function (Builder $query) use ($filters) {
                $query->where('subject_type', $filters['subject_type']);
            })
            ->when(isset($filters['event']), function (Builder $query) use ($filters) {
                $query->where('event', $filters['event']);
            })
            ->when(isset($filters['user_id']), function (Builder $query) use ($filters) {
                $query->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['date_from']), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(isset($filters['date_to']), function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->with(['causer', 'subject'])
            ->latest();
        
        return $query->paginate($perPage);
    }
} 