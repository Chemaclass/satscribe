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

    public function getForceRefreshAttribute(): bool
    {
        $firstMsg = $this->relationLoaded('messages')
            ? $this->messages->first()
            : $this->messages()->first();

        return (bool) ($firstMsg?->meta['force_refresh'] ?? false);
    }

    public function getTypeAttribute(): string
    {
        $firstMsg = $this->relationLoaded('messages')
            ? $this->messages->first()
            : $this->messages()->first();

        return $firstMsg?->meta['type'] ?? '';
    }

    public function getInputAttribute(): string
    {
        $firstMsg = $this->relationLoaded('messages')
            ? $this->messages->first()
            : $this->messages()->first();

        return $firstMsg?->meta['input'] ?? '';
    }
}
