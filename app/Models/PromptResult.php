<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PromptResult extends Model
{
    protected $fillable = [
        'type',
        'input',
        'ai_response',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}
