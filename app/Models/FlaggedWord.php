<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class FlaggedWord extends Model
{
    protected $fillable = [
        'word',
        'severity',
        'is_active',
    ];
}
