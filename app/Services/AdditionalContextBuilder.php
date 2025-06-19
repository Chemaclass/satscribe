<?php
declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;

final readonly class AdditionalContextBuilder
{
    public function __construct(private BlockchainService $blockchain)
    {
    }

    public function build(BlockchainData $baseData, PromptInput $currentInput, string $question): string
    {
        $sections = [];

        if (preg_match('/tx\s*["\']?([a-f0-9]{64})["\']?/i', $question, $m)) {
            $txInput = PromptInput::fromRaw($m[1]);
            if ($txInput->text !== $currentInput->text) {
                $txData = $this->blockchain->getBlockchainData($txInput);
                $sections[] = "Referenced Transaction\n" . $txData->current()->toPrompt();
            }
        }

        if (preg_match('/block\s*(\d+|0{8,}[a-f0-9]{56})/i', $question, $m)) {
            $blockInput = PromptInput::fromRaw($m[1]);
            if ($blockInput->text !== $currentInput->text) {
                $blockData = $this->blockchain->getBlockchainData($blockInput);
                $sections[] = "Referenced Block\n" . $blockData->current()->toPrompt();
            }
        }

        if ($currentInput->isBlock()) {
            $lower = strtolower($question);
            if (str_contains($lower, 'previous')) {
                if ($baseData->previousBlock !== null) {
                    $sections[] = "Previous Block\n" . $baseData->previousBlock->toPrompt();
                }
            }
            if (str_contains($lower, 'next')) {
                if ($baseData->nextBlock !== null) {
                    $sections[] = "Next Block\n" . $baseData->nextBlock->toPrompt();
                }
            }
        }

        return implode("\n\n", $sections);
    }
}
