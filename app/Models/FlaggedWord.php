<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Domain\Enum\FlaggedWordSeverity;

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
