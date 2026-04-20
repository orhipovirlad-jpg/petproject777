<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'email',
        'subscription_plan_id',
        'amount',
        'currency',
        'starts_at',
        'ends_at',
        'status',
        'last_payment_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function lastPayment(): BelongsTo
    {
        return $this->belongsTo(ProPayment::class, 'last_payment_id');
    }
}
