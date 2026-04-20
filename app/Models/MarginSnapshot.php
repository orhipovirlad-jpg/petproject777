<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarginSnapshot extends Model
{
    protected $fillable = [
        'email',
        'title',
        'platform',
        'input_payload',
        'result_payload',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'result_payload' => 'array',
    ];
}
