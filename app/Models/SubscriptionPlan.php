<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'price_monthly',
        'currency',
        'limits',
        'features',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'limits' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'price_monthly' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
