<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentDetail extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'verification_documents' => 'array',
        'supported_currencies' => 'array',
        'balance_accounts' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 
