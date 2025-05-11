<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Conversation extends Model
{
    protected $fillable = [
        'title',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
