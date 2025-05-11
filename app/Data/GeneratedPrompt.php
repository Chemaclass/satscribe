<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Conversation;

final readonly class GeneratedPrompt
{
    /**
     * @param  bool  $isFresh  true if newly generated, false if loaded from DB
     */
    public function __construct(
        public Conversation $conversation,
        public bool $isFresh,
    ) {
    }
}
