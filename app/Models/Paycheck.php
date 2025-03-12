<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paycheck extends Model
{
    protected $fillable = [
        'container_id',
        'project_id',
        'created_by',
        'total_hours',
        'total_amount',
        'status',
        'payment_method',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2'
    ];

    public function wiseTransfer(): HasOne
    {
        return $this->hasOne(WiseTransfer::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
