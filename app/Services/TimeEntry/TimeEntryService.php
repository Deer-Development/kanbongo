<?php

namespace App\Services\TimeEntry;

use App\Models\TimeEntry;
use App\Services\BaseService;

class TimeEntryService extends BaseService
{
    protected string $modelClass = TimeEntry::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }
}
