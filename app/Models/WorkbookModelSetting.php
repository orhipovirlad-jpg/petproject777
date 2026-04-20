<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Relation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'model',
    'commission_percent',
    'buyout_percent',
    'logistics_base_cost',
    'storage_daily_cost',
    'storage_days',
    'extra_cost',
    'ad_spend_percent',
    'tax_percent',
    'mp_discount_percent',
    'acquiring_percent',
    'last_mile_cost',
])]
class WorkbookModelSetting extends Model
{
    protected function casts(): array
    {
        return [
            'commission_percent' => 'float',
            'buyout_percent' => 'float',
            'logistics_base_cost' => 'float',
            'storage_daily_cost' => 'float',
            'storage_days' => 'float',
            'extra_cost' => 'float',
            'ad_spend_percent' => 'float',
            'tax_percent' => 'float',
            'mp_discount_percent' => 'float',
            'acquiring_percent' => 'float',
            'last_mile_cost' => 'float',
        ];
    }

    #[Relation]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
