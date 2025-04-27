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

        // 1. System and Persona Context
        $sections[] = <<<TEXT
{$persona->systemPrompt()}.
You will receive structured blockchain data for CONTEXT ONLY.
Do NOT mechanically list or repeat back the data.
Your role is to craft an insightful, persona-aligned response.
Prioritize clarity, brevity, and meaningful key takeaways over exhaustive details.
TEXT;

        // 2. Task Instructions based on type
        if ($type === PromptType::Transaction) {
            $additionalTask = <<<TEXT
- Identify the transaction type (e.g., coinbase, CoinJoin-like, P2PK, P2PKH, P2SH, P2MS, P2WPKH, P2WSH, P2TR, etc.).
- Highlight unusual input/output patterns (e.g., large numbers of inputs/outputs, consolidation behavior, privacy techniques).
- Mention if the transaction paid exceptionally high fees relative to its size.
- If the transaction appears to be a consolidation or CoinJoin, explain briefly.
TEXT;
        } else { // Block
            $additionalTask = <<<TEXT
- Highlight if the block has only one transaction, an unusually low or high transaction count, or exceptionally large total fees.
- Mention if the coinbase transaction contains an OP_RETURN output.
- Compare size, timestamp, and miner with adjacent blocks if noteworthy.
- Mention if the miner is notable, changed recently, or unexpected.
- Highlight any anomalies (size, timestamp gaps, etc.).
- If the block has historical significance, clearly explain why.
TEXT;
        }

        $sections[] = <<<TEXT
Task:
- Answer the provided question (if any) FIRST.
- Then summarize the most relevant insights from the blockchain context.
- Do NOT fabricate missing data.
- Do NOT repeat information already stated.
- Focus on what is interesting or unusual, not exhaustive lists.
- The values are satoshis.
$additionalTask
TEXT;

        // 3. Global Writing Instructions
        $sections[] = <<<TEXT
Writing Style:
- Use markdown formatting.
- Keep paragraphs under 80 words.
- Use bullet points where appropriate for clarity.
- Focus on actionable, concise insights.
- End the answer naturally without abrupt cut-offs.
TEXT;

        // 4. Question-specific instructions
        $sections[] = $questionInstructions;

        // 5. Blockchain context (always last)
        $sections[] = "Blockchain Data Context:\n" . $data->toPrompt();

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
