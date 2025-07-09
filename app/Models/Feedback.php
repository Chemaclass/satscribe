<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'nickname',
        'email',
        'message',
    ];
}
