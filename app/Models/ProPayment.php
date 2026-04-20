<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProPayment extends Model
{
    protected $fillable = [
        'email',
        'amount',
        'currency',
        'provider',
        'provider_payment_id',
        'idempotence_key',
        'status',
        'confirmation_url',
        'paid_at',
        'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'paid_at' => 'datetime',
    ];
}
