<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\PromptResult;

final readonly class DescribedPrompt
{
    /**
     * @param  bool  $isFresh  true if newly generated, false if loaded from DB
     */
    public function __construct(
        public PromptResult $result,
        public bool $isFresh,
    ) {
    }
}
