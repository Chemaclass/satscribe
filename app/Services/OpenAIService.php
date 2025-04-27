<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class OpenAIService
{
    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
        private string $openAiApiKey,
        private string $openAiModel,
    ) {
    }

    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question = '',
    ): string {
        $payload = [
            'model' => $this->openAiModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->preparePrompt($data, $input->type, $persona, $question),
                ],
            ],
            'max_tokens' => $persona->maxTokens(),
        ];

        $response = $this->http->withToken($this->openAiApiKey)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($error = $response->json('error.message')) {
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $text = $this->trimToLastFullSentence($text);
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }

    private function preparePrompt(
        BlockchainData $data,
        PromptType $type,
        PromptPersona $persona,
        string $question,
    ): string {
        $questionInstructions = $question ?: $this->defaultQuestionInstructions($type);

        $sections = [];

        $sections[] = <<<TEXT
{$persona->systemPrompt()}.
You will receive structured blockchain data for context purposes only.
DO NOT mechanically list all fields or repeat the data back.
Use the provided data to craft an insightful, persona-aligned answer.
Prioritize good writing and clarity over exhaustive detail.
TEXT;
        if ($type === PromptType::Transaction) {
            $additionalTask = <<<TEXT
- Identify the transaction type (e.g., coinbase, CoinJoin-like, P2PK, P2PKH, P2SH, P2MS, P2WPKH, P2WSH, P2TR, etc.).
- Highlight any unusual input/output patterns (e.g., unusually large number of inputs, outputs, high consolidation, or privacy techniques).
- If the transaction paid exceptionally high fees relative to its size, mention it.
- If the transaction appears to be a consolidation or CoinJoin, explain briefly.
TEXT;
        } else { // Block
            $additionalTask = <<<TEXT
- Highlight if the block has only one transaction, an unusually low or high transaction count, or exceptionally large total fees.
- Mention if the block's coinbase transaction contains an OP_RETURN output.
- Compare key attributes (timestamp, size, miner) with the previous and next block if interesting patterns are observed.
- Point out if the miner is notable, unexpected, or has changed compared to adjacent blocks.
- Mention if the block has unusual size, timestamp gaps, or other anomalies.
- If the block has historical significance, clearly state why.
TEXT;
        }
        $sections[] = <<<TEXT
Task:
- Answer the provided question (if any) FIRST.
- Do NOT fabricate missing data.
- Do NOT repeat information already given or answered within the context.
$additionalTask

Instructions:
- Use markdown formatting.
- Paragraphs should not exceed 80 words.
- Write clearly, concisely, and avoid technical dumps.
- Focus on insights, not mechanical repetition.
TEXT;
        if ($type === PromptType::Block) {
            $sections[] = "- If the block has only one transaction or something extraordinary happens, mention it.";
        }

        $sections[] = $questionInstructions;
        $sections[] = "Blockchain Data Context:\n".$data->toPrompt();

        return implode("\n\n", $sections);
    }

    private function defaultQuestionInstructions(PromptType $type): string
    {
        return <<<TEXT
- Use markdown formatting.
- Keep paragraphs below 80 words.
- Explicitly mention if this {$type->value} is historically important.
TEXT;
    }

    private function trimToLastFullSentence(string $text): string
    {
        $matches = [];
        preg_match_all('/[.?!](?=\s|$)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && $matches[0] !== []) {
            $last = end($matches[0]);
            return mb_substr($text, 0, $last[1] + 1);
        }

        return $text; // fallback
    }
}
