<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerFilter extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'filters' => 'array',
    ];
}
