<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalculationRequest extends Model
{
    protected $fillable = [
        'ip_address',
        'platform',
        'email',
        'cost_price',
        'packaging_cost',
        'logistics_cost',
        'commission_percent',
        'ad_spend_percent',
        'tax_percent',
        'returns_percent',
        'desired_margin_percent',
        'planned_units',
        'break_even_price',
        'recommended_price',
        'net_profit',
    ];
}
