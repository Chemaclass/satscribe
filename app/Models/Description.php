<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Description extends Model
{
    protected $fillable = [
        'type',
        'input',
        'description',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}
