<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'activities_enabled' => 'boolean',
        'member_report_enabled' => 'boolean',
        'owner_report_enabled' => 'boolean',
        'daily_report_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 

