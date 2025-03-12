<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WiseTransfer extends Model
{
    protected $fillable = [
        'paycheck_id',
        'wise_transfer_id',
        'wise_recipient_id',
        'target_account_id',
        'quote_id',
        'status',
        'source_amount',
        'source_currency',
        'target_amount',
        'target_currency',
        'rate',
        'reference',
        'raw_response'
    ];

    protected $casts = [
        'raw_response' => 'array',
        'source_amount' => 'decimal:2',
        'target_amount' => 'decimal:2',
        'rate' => 'decimal:6'
    ];

    public function paycheck(): BelongsTo
    {
        return $this->belongsTo(Paycheck::class);
    }
} 