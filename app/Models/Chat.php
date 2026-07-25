<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

use function count;

class Chat extends Model
{
    protected $table = 'chats';

    protected $fillable = [
        'title',
        'ulid',
        'tracking_id',
        'is_public',
        'is_shared',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_shared' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getFirstUserMessage(): Message
    {
        if ($this->relationLoaded('messages')) {
            $message = $this->messages->first(static fn (Message $m): bool => $m->role === 'user');
            if ($message === null) {
                throw new ModelNotFoundException();
            }

            return $message;
        }

        return $this->messages()
            ->where('role', 'user')
            ->orderBy('id')
            ->firstOrFail();
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getLastUserMessage(): Message
    {
        if ($this->relationLoaded('messages')) {
            $message = $this->messages->last(static fn (Message $m): bool => $m->role === 'user');
            if ($message === null) {
                throw new ModelNotFoundException();
            }

            return $message;
        }

        return $this->messages()
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->firstOrFail();
    }

    public function getFirstAssistantMessage(): Message
    {
        if ($this->relationLoaded('messages')) {
            $message = $this->messages->first(static fn (Message $m): bool => $m->role === 'assistant');
            if ($message === null) {
                throw new ModelNotFoundException();
            }

            return $message;
        }

        return $this->messages()
            ->where('role', 'assistant')
            ->orderBy('id')
            ->firstOrFail();
    }

    public function getLastAssistantMessage(): Message
    {
        if ($this->relationLoaded('messages')) {
            $message = $this->messages->last(static fn (Message $m): bool => $m->role === 'assistant');
            if ($message === null) {
                throw new ModelNotFoundException();
            }

            return $message;
        }

        return $this->messages()
            ->where('role', 'assistant')
            ->orderBy('id', 'desc')
            ->firstOrFail();
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

    /**
     * @param  array<string, mixed>  $meta  persisted verbatim to the JSON `meta` column
     */
    public function addUserMessage(string $content, array $meta = []): Message
    {
        return $this->messages()->create([
            'role' => 'user',
            'content' => $content,
            'meta' => $meta,
        ]);
    }

    /**
     * @param  array<string, mixed>  $meta  persisted verbatim to the JSON `meta` column
     */
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
        $firstMessage = $this->relationLoaded('messages')
            ? $this->messages->first()
            : $this->messages()->first();

        if (!$firstMessage instanceof Message) {
            throw new ModelNotFoundException();
        }

        return $firstMessage->isBlock();
    }

    /**
     * Pair each user message with its assistant reply. A trailing unpaired
     * message yields a group with the other side null.
     *
     * @return list<array{userMsg: Message|null, assistantMsg: Message|null}>
     */
    public function messageGroups(): array
    {
        $messages = array_values($this->messages->all());
        $count = count($messages);
        $groups = [];

        for ($i = 0; $i < $count - 1; ++$i) {
            if ($messages[$i]->role === 'user' && $messages[$i + 1]->role === 'assistant') {
                $groups[] = [
                    'userMsg' => $messages[$i],
                    'assistantMsg' => $messages[$i + 1],
                ];
                ++$i;
            }
        }

        // Handle last message if odd count
        if ($i < $count) {
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
        $chatMessages = $this->relationLoaded('messages')
            ? $this->messages->sortBy('created_at')
            : $this->messages()->orderBy('created_at')->get();

        return array_values(
            $chatMessages
                ->map(static fn (Message $msg) => [
                    'role' => (string) $msg->role,
                    'content' => (string) $msg->content,
                ])
                ->all(),
        );
    }

    public function canShow(string $trackingId): bool
    {
        return $trackingId === $this->tracking_id
            || $this->is_public
            || $this->is_shared;
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
