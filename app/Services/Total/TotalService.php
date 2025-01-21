<?php

namespace App\Services\Total;

use App\Models\Total;
use App\Services\BaseService;

class TotalService extends BaseService
{
    protected string $modelClass = Total::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }
}
