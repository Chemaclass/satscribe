<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SatscribeDescription extends Model
{
    protected $fillable = [
        'type',
        'input',
        'question',
        'ai_response',
        'raw_data',
        'force_refresh',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'force_refresh' => 'boolean',
    ];
}
