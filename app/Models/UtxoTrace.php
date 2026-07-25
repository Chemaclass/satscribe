<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UtxoTrace extends Model
{
    protected $fillable = [
        'txid',
        'depth',
        'version',
        'result',
    ];

    protected $casts = [
        'result' => 'array',
        'depth' => 'integer',
        'version' => 'integer',
    ];
}
