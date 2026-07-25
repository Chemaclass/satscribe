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
        return $this->messageWithRole('user');
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
        return $this->messageWithRole('user', last: true);
    }

    public function getFirstAssistantMessage(): Message
    {
        return $this->messageWithRole('assistant');
    }

    public function getLastAssistantMessage(): Message
    {
        return $this->messageWithRole('assistant', last: true);
    }

    public function getForceRefreshAttribute(): bool
    {
        $firstMsg = $this->firstMessage();

        return (bool) ($firstMsg?->meta['force_refresh'] ?? false);
    }

    public function getTypeAttribute(): string
    {
        $firstMsg = $this->firstMessage();

        return $firstMsg?->meta['type'] ?? '';
    }

    public function getInputAttribute(): string
    {
        $firstMsg = $this->firstMessage();

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
        $firstMessage = $this->firstMessage();

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

    /**
     * The oldest message whatever its role. Ordered explicitly so the query
     * branch cannot disagree with the eager-loaded one about which is first.
     */
    private function firstMessage(): ?Message
    {
        return $this->relationLoaded('messages')
            ? $this->messages->first()
            : $this->messages()->orderBy('id')->first();
    }

    /**
     * Reads the eager-loaded relation when there is one so a loaded chat never
     * issues another query, and raises a not-found either way when the role is
     * absent.
     */
    private function messageWithRole(string $role, bool $last = false): Message
    {
        if ($this->relationLoaded('messages')) {
            $matches = $this->messages->filter(static fn (Message $m): bool => $m->role === $role);
            $message = $last ? $matches->last() : $matches->first();

            if (!$message instanceof Message) {
                throw new ModelNotFoundException();
            }

            return $message;
        }

        return $this->messages()
            ->where('role', $role)
            ->orderBy('id', $last ? 'desc' : 'asc')
            ->firstOrFail();
    }
}
