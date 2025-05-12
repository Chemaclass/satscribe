<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function isBlock(): bool
    {
        return $this->meta['type'] === 'block';
    }

    public function getTypeAttribute(): ?string
    {
        return $this->meta['type'] ?? null;
    }

    public function getInputAttribute(): ?string
    {
        return $this->meta['input'] ?? null;
    }

    public function getPersonaAttribute(): ?string
    {
        return $this->meta['persona'] ?? null;
    }

    public function getRawDataAttribute(): mixed
    {
        return $this->meta['raw_data'] ?? null;
    }

    public function getForceRefreshAttribute(): bool
    {
        return $this->meta['force_refresh'] ?? false;
    }

    public function getQuestionAttribute(): ?string
    {
        return $this->meta['question'] ?? null;
    }
}
