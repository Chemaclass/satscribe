<?php

namespace App\Models;

use App\Enums\FlaggedWordSeverity;
use Illuminate\Database\Eloquent\Model;

final class FlaggedWord extends Model
{
    protected $fillable = [
        'word',
        'severity',
        'is_active',
    ];

    protected $casts = [
        'severity' => FlaggedWordSeverity::class,
        'is_active' => 'boolean',
    ];
}
