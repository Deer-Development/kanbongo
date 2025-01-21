<?php

namespace App\Services\Member;

use App\Models\Member;
use App\Services\BaseService;

class MemberService extends BaseService
{
    protected string $modelClass = Member::class;

    // Define valid columns for sorting
    protected function getValidSortColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }
}
