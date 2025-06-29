<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $table = 'chats';

    protected $fillable = [
        'title',
        'ulid',
        'tracking_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getFirstUserMessage(): Message
    {
        return $this->messages()
            ->where('role', 'user')
            ->orderBy('id')
            ->firstOrFail();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getLastUserMessage(): Message
    {
        return $this->messages()
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getFirstAssistantMessage(): Message
    {
        return $this->messages()
            ->where('role', 'assistant')
            ->orderBy('id')
            ->firstOrFail();
    }

    public function getLastAssistantMessage(): Message
    {
        return $this->messages()
            ->where('role', 'assistant')
            ->orderBy('id', 'desc')
            ->first();
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

    public function addUserMessage(string $content, array $meta = []): Message
    {
        return $this->messages()->create([
            'role' => 'user',
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    public function addAssistantMessage(string $content, array $meta = []): Message
    {
        return $this->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    public function isBlock(): bool
    {
        return $this->messages()->first()->isBlock();
    }

    public function messageGroups(): array
    {
        $messages = $this->messages->values();
        $groups = [];

        for ($i = 0; $i < $messages->count() - 1; ++$i) {
            if ($messages[$i]->role === 'user' && $messages[$i + 1]->role === 'assistant') {
                $groups[] = [
                    'userMsg' => $messages[$i],
                    'assistantMsg' => $messages[$i + 1],
                ];
                ++$i;
            }
        }

        // Handle last message if odd count
        if ($i < $messages->count()) {
            if ($messages[$i]->role === 'user') {
                $groups[] = ['userMsg' => $messages[$i], 'assistantMsg' => null];
            } else {
                $groups[] = ['userMsg' => null, 'assistantMsg' => $messages[$i]];
            }
        }

        return $groups;
    }

    /**
     * @return list<array{role:string, content:string}>
     */
    public function getHistory(): array
    {
        $chatMessages = $this->messages()->orderBy('created_at')->get();

        return $chatMessages
            ->map(static fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function ($model): void {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) Str::ulid());
            }
        });
    }
}
