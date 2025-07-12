<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use App\Models\Chat;
use Modules\Chat\Domain\Data\QuestionPlaceholder;

use function in_array;

final readonly class SuggestedPromptService
{
    /**
     * Get grouped suggested prompts.
     * For a new chat (null), return random prompts from examples.
     * For an existing chat, discard prompts that have already been used by the user.
     *
     * @return array<string, list<string>>
     */
    public function getGroupedPrompts(?Chat $chat = null): array
    {
        $used = [];
        if ($chat instanceof Chat) {
            $used = $chat->messages()
                ->where('role', 'user')
                ->pluck('content')
                ->map(static fn (string $p) => trim($p))
                ->all();
        }

        return collect(QuestionPlaceholder::SAMPLE_QUESTIONS)
            ->map(static fn (array $prompts) => collect($prompts)
                ->reject(static fn (string $prompt) => in_array($prompt, $used, true))
                ->shuffle()
                ->take(2)
                ->map(static fn (string $prompt) => __($prompt))
                ->values())
            ->all();
    }
}
