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
                    'role' => 'system',
                    'content' => $persona->systemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->preparePrompt($data, $input->type, $question, $persona),
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
        string $question,
        PromptPersona $persona
    ): string {
        $sections = [];

        $sections[] = <<<TEXT
Task Instructions:
- If a question is provided, answer it directly and briefly FIRST.
- Then summarize the most relevant insights from the blockchain context.
- Focus on insights that are:
    - New
    - Surprising
    - Non-obvious
    - Historically or technically meaningful
- DO NOT fabricate missing information.
- DO NOT merely repeat the provided data.
- All numeric values are denominated in satoshis.
TEXT;

        $sections[] = $this->getAdditionalTaskInstructions($type);
        $sections[] = $persona->instructions($type);

        $sections[] = <<<TEXT
Writing Style:
- Use markdown formatting (headers, bullet points, and emphasis where appropriate).
- Prefer active voice over passive voice.
- Keep sentences and paragraphs concise (aim for under 80 words per paragraph).
- Group related ideas logically.
- Maintain a professional yet accessible tone.
- End the response naturally without abrupt cut-offs.
TEXT;

        if ($question !== '') {
            $sections[] = "User Question:\n{$question}";
        }

        $sections[] = <<<TEXT
Blockchain Data Context:
All insights must be grounded in the following data.
Do not fabricate or repeat. Interpret and summarize meaningfully.
{$data->toPrompt()}
TEXT;

        return implode("\n\n", $sections);
    }

    private function trimToLastFullSentence(string $text): string
    {
        $text = trim($text);

        // Match sentence-ending punctuation followed by space or end
        // Handles English and common Unicode (e.g., …)
        $matches = [];
        preg_match_all('/[.?!…](?=\s|$)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if (!empty($matches[0])) {
            $last = end($matches[0]);
            $cutPos = $last[1] + mb_strlen($last[0]);
            $clean = mb_substr($text, 0, $cutPos);

            // Final cleanup: remove hanging markdown artifacts
            $clean = preg_replace('/(\*\*|\*|_|\-)+$/u', '', $clean);

            return trim($clean);
        }

        return $text; // fallback: no sentence-ending punctuation found
    }

    private function getAdditionalTaskInstructions(PromptType $type): string
    {
        if ($type === PromptType::Transaction) {
            return <<<TEXT
- Identify the transaction type (e.g., coinbase, CoinJoin-like, P2PK, P2PKH, P2SH, P2MS, P2WPKH, P2WSH, P2TR, etc.).
- Highlight unusual input/output patterns (e.g., large numbers of inputs/outputs, consolidation behavior, privacy techniques).
- Mention if the transaction paid exceptionally high fees relative to its size.
TEXT;
        }

        // Block
        return <<<TEXT
- Highlight if the block has only one transaction, an unusually low or high transaction count, or exceptionally large total fees.
- Compare size, timestamp, and miner with adjacent blocks if noteworthy.
- Mention if the miner is notable, changed recently, or unexpected.
- Highlight any anomalies (size, timestamp gaps, etc.).
- If the block has historical significance, clearly explain why.
TEXT;
    }
}
