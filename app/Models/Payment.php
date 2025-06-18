<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Payment extends Model
{
    protected $fillable = [
        'tracking_id',
        'chat_id',
        'payment_hash',
        'memo',
        'amount',
        'status',
        'failure_reason',
    ];
}
