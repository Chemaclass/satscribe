<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;
use App\Models\Chat;
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
        string $question,
        ?Chat $chat = null,
    ): string {
        $history = collect($chat?->getHistory() ?? [])
            ->take(-5) // gets the last 5 messages
            ->values()
            ->all();

        $messages = [
            [
                'role' => 'system',
                'content' => $persona->systemPrompt(),
            ],
            ...$history,
            [
                'role' => 'user',
                'content' => $this->buildBlockchainContext($data),
            ],
            [
                'role' => 'user',
                'content' => $this->preparePrompt($input->type, $question, $persona),
            ],
        ];

        $response = $this->http->withToken($this->openAiApiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openAiModel,
                'messages' => $messages,
                'max_tokens' => $persona->maxTokens(),
            ]);

        if ($error = $response->json('error.message')) {
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $text = $this->trimToLastFullSentence($text);
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }

    private function preparePrompt(
        PromptType $type,
        string $question,
        PromptPersona $persona
    ): string {
        return implode("\n\n", array_filter([
            ($question === '' || $question === HomeIndexRequest::DEFAULT_USER_QUESTION)
                ? $this->buildDefaultInsightPrompt($type, $persona)
                : $this->buildQuestionPrompt($question),

            $this->buildWritingStyleInstructions(),
        ]));
    }

    private function buildDefaultInsightPrompt(PromptType $type, PromptPersona $persona): string
    {
        return implode("\n", [
            "Task: Summarize insights from blockchain data.",
            "- Focus on: new, surprising, or non-obvious patterns.",
            "- Don't fabricate or repeat the raw data.",
            "- All values are in satoshis.",
            $this->getAdditionalTaskInstructions($type),
            $persona->instructions($type),

        ]);
    }

    private function buildQuestionPrompt(string $question): string
    {
        return <<<TEXT
User Question:
{$question}

Guidelines:
- Assume the question refers to the current block or transaction unless obviously unrelated.
- Ignore non-Bitcoin queries with a polite response.
- Base your answer solely on the provided blockchain data.
TEXT;
    }

    private function buildWritingStyleInstructions(): string
    {
        return <<<TEXT
Style:
- Use markdown if helpful.
- Prefer active voice.
- Keep paragraphs short and well-structured.
- Sound professional but accessible.
TEXT;
    }

    private function buildBlockchainContext(BlockchainData $data): string
    {
        return "Data:\n".$data->toPrompt();
    }

    private function getAdditionalTaskInstructions(PromptType $type): string
    {
        return $type === PromptType::Transaction
            ? <<<TEXT
- Identify the transaction type (e.g., coinbase, CoinJoin-like, P2PK, P2PKH, P2SH, P2MS, P2WPKH, P2WSH, P2TR, etc.).
- Highlight unusual input/output patterns (e.g., large numbers of inputs/outputs, consolidation behavior, privacy techniques).
- Mention if the transaction paid exceptionally high fees relative to its size.
TEXT
            : <<<TEXT
- Highlight if the block has only one transaction, an unusually low or high transaction count, or exceptionally large total fees.
- Compare size, timestamp, and miner with adjacent blocks if noteworthy.
- Mention if the miner is notable, changed recently, or unexpected.
- Highlight any anomalies (size, timestamp gaps, etc.).
- If the block has historical significance, clearly explain why.
TEXT;
    }

    private function trimToLastFullSentence(string $text): string
    {
        $text = trim($text);

        preg_match_all('/[.?!…](?=\s|$)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && $matches[0] !== []) {
            $last = end($matches[0]);
            $cutPos = $last[1] + mb_strlen($last[0]);
            $clean = mb_substr($text, 0, $cutPos);

            return trim((string) preg_replace('/(\*\*|\*|_|\-)+$/u', '', $clean));
        }

        return $text;
    }
}
