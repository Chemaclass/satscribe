<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PremiumCreditEntry extends Model
{
    public const REASON_PURCHASE = 'purchase';
    public const REASON_SPEND = 'spend';

    protected $fillable = [
        'npub',
        'delta',
        'reason',
        'payment_hash',
    ];

    protected $casts = [
        'delta' => 'integer',
    ];
}
