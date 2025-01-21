<?php

namespace App\Models;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Total extends Model
{
    use SoftDeletes, Filterable;

    protected $guarded = ['id'];

    //
}
