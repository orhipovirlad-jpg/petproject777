<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Relation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'group',
    'name',
    'sku',
    'barcode',
    'purchase_price',
    'agent_percent',
    'defect_percent',
    'delivery_cost',
    'marking_cost',
    'storage_cost',
    'packaging_cost',
    'stock',
    'sale_price',
    'discount_percent',
])]
class WorkbookProduct extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_price' => 'float',
            'agent_percent' => 'float',
            'defect_percent' => 'float',
            'delivery_cost' => 'float',
            'marking_cost' => 'float',
            'storage_cost' => 'float',
            'packaging_cost' => 'float',
            'stock' => 'integer',
            'sale_price' => 'float',
            'discount_percent' => 'float',
        ];
    }

    #[Relation]
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
