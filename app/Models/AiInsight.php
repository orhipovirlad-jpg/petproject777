<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $fillable = [
        'calculation_request_id',
        'type',
        'source',
        'email',
        'ip_address',
        'status',
        'input_payload',
        'output_payload',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'output_payload' => 'array',
    ];
}
