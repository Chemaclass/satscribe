<?php
declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Blockchain\Domain\BlockchainServiceInterface;
use Modules\Blockchain\Domain\Data\BlockchainData;
use Modules\Blockchain\Domain\Data\BlockData;
use Modules\Chat\Domain\Data\PromptInput;
use Modules\UtxoTrace\Application\TransactionBacktraceService;

final readonly class AdditionalContextBuilder
{
    public function __construct(
        private BlockchainServiceInterface $blockchainService,
        private TransactionBacktraceService $backtraceService,
    ) {
    }

    public function build(BlockchainData $baseData, PromptInput $currentInput, string $question): string
    {
        $sections = [];

        if (preg_match('/tx\s*["\']?([a-f0-9]{64})["\']?/i', $question, $m)) {
            $txInput = PromptInput::fromRaw($m[1]);
            if ($txInput->text !== $currentInput->text) {
                $txData = $this->blockchainService->getBlockchainData($txInput);
                $sections[] = "Referenced Transaction\n" . $txData->current()->toPrompt();
            }
        }

        if (preg_match('/block\s*(\d+|0{8,}[a-f0-9]{56})/i', $question, $m)) {
            $blockInput = PromptInput::fromRaw($m[1]);
            if ($blockInput->text !== $currentInput->text) {
                $blockData = $this->blockchainService->getBlockchainData($blockInput);
                $sections[] = "Referenced Block\n" . $blockData->current()->toPrompt();
            }
        }

        if (preg_match('/back-?trace/i', $question)) {
            $txid = $currentInput->isTransaction() ? $currentInput->text : null;
            if (preg_match('/tx\s*["\']?([a-f0-9]{64})["\']?/i', $question, $m)) {
                $txid = $m[1];
            }

            if ($txid !== null) {
                $trace = $this->backtraceService->getBacktrace($txid);
                $sections[] = $this->backtraceService->formatForPrompt($trace);
            }
        }

        if ($currentInput->isBlock()) {
            $lower = strtolower($question);
            if (str_contains($lower, 'previous') && $baseData->previousBlock instanceof BlockData) {
                $sections[] = "Previous Block\n" . $baseData->previousBlock->toPrompt();
            }
            if (str_contains($lower, 'next') && $baseData->nextBlock instanceof BlockData) {
                $sections[] = "Next Block\n" . $baseData->nextBlock->toPrompt();
            }
        }

        return implode("\n\n", $sections);
    }
}
