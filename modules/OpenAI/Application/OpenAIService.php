<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use App\Models\Chat;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Http\Client\Factory as HttpFactory;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\Shared\Domain\Chat\ChatConstants;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Blockchain\TransactionData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;

use Modules\Shared\Domain\HttpClientInterface;
use Psr\Log\LoggerInterface;

use function sprintf;
use function strlen;

final readonly class OpenAIService
{
    public function __construct(
        private HttpClientInterface $http,
        private HttpFactory $httpFactory,
        private LoggerInterface $logger,
        private PersonaPromptBuilder $promptBuilder,
        private PriceServiceInterface $priceService, // @todo use BlockchainFacade instead
        private CarbonInterface $now,
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
        string $additionalContext = '',
    ): string {
        $this->logger->debug('Calling OpenAI API', [
            'model' => $this->openAiModel,
            'persona' => $persona->value,
        ]);
        $history = collect($chat?->getHistory() ?? [])
            ->take(-5) // gets the last 5 messages
            ->values()
            ->all();

        $timestamp = 0;
        if ($data->block instanceof BlockData) {
            $timestamp = $data->block->timestamp;
        } elseif ($data->transaction instanceof TransactionData) {
            $timestamp = $data->transaction->blockTime ?? 0;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->promptBuilder->buildSystemPrompt($persona),
            ],
            ...$history,
            [
                'role' => 'user',
                'content' => $this->buildBlockchainContext($data, $additionalContext),
            ],
            [
                'role' => 'user',
                'content' => $this->preparePrompt($input->type, $question, $persona, $timestamp),
            ],
        ];

        $response = $this->http->withToken($this->openAiApiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openAiModel,
                'messages' => $messages,
                'max_tokens' => $persona->maxTokens(),
            ]);

        if ($response->failed()) {
            $this->logger->error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new OpenAIError('OpenAI API request failed');
        }

        if ($error = $response->json('error.message')) {
            $this->logger->error('OpenAI API responded with an error', [
                'error' => $error,
                'status' => $response->status(),
            ]);
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $text = $this->trimToLastFullSentence($text);
        $this->logger->debug('OpenAI description generation worked', ['length' => strlen($text)]);

        return $text;
    }

    /**
     * @return Generator<string>
     */
    public function generateTextStreaming(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
    ): Generator {
        $this->logger->debug('Calling OpenAI API with streaming', [
            'model' => $this->openAiModel,
            'persona' => $persona->value,
        ]);

        $history = collect($chat?->getHistory() ?? [])
            ->take(-5)
            ->values()
            ->all();

        $timestamp = 0;
        if ($data->block instanceof BlockData) {
            $timestamp = $data->block->timestamp;
        } elseif ($data->transaction instanceof TransactionData) {
            $timestamp = $data->transaction->blockTime ?? 0;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->promptBuilder->buildSystemPrompt($persona),
            ],
            ...$history,
            [
                'role' => 'user',
                'content' => $this->buildBlockchainContext($data, $additionalContext),
            ],
            [
                'role' => 'user',
                'content' => $this->preparePrompt($input->type, $question, $persona, $timestamp),
            ],
        ];

        $response = $this->httpFactory
            ->withToken($this->openAiApiKey)
            ->withOptions(['stream' => true])
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->openAiModel,
                'messages' => $messages,
                'max_tokens' => $persona->maxTokens(),
                'stream' => true,
            ]);

        if ($response->failed()) {
            $this->logger->error('OpenAI API streaming request failed', [
                'status' => $response->status(),
            ]);

            return;
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                if (str_starts_with($line, 'data: ')) {
                    $jsonData = substr($line, 6);

                    if ($jsonData === '[DONE]') {
                        return;
                    }

                    $decoded = json_decode($jsonData, true);
                    $content = $decoded['choices'][0]['delta']['content'] ?? null;

                    if ($content !== null) {
                        yield $content;
                    }
                }
            }
        }
    }

    private function buildBlockchainContext(BlockchainData $data, string $additional): string
    {
        $content = "Data:\n" . $data->toPrompt();

        if ($additional !== '') {
            $content .= "\n\n---\nAdditional Data\n" . $additional;
        }

        return $content;
    }

    private function buildPriceLine(int $timestamp): string
    {
        $currentUsd = $this->priceService->getCurrentBtcPriceUsd();
        $currentEur = $this->priceService->getCurrentBtcPriceEur();

        if (Carbon::createFromTimestamp($timestamp)->lt($this->now->copy()->subYear())) {
            if ($currentUsd <= 0 && $currentEur <= 0) {
                return '';
            }

            return sprintf(
                'Today 1 BTC is about $%s USD or €%s EUR.',
                number_format($currentUsd, 0),
                number_format($currentEur, 0),
            );
        }

        $historicUsd = $this->priceService->getBtcPriceUsdAt($timestamp);
        $historicEur = $this->priceService->getBtcPriceEurAt($timestamp);

        if ($historicUsd <= 0 && $historicEur <= 0) {
            if ($currentUsd <= 0 && $currentEur <= 0) {
                return '';
            }

            return sprintf(
                'Today 1 BTC is about $%s USD or €%s EUR.',
                number_format($currentUsd, 0),
                number_format($currentEur, 0),
            );
        }

        return sprintf(
            'At that time, 1 BTC was about $%s USD or €%s EUR. Today it is about $%s USD or €%s EUR.',
            number_format($historicUsd, 0),
            number_format($historicEur, 0),
            number_format($currentUsd, 0),
            number_format($currentEur, 0),
        );
    }

    private function preparePrompt(
        PromptType $type,
        string $question,
        PromptPersona $persona,
        int $timestamp,
    ): string {
        return implode("\n\n", array_filter([
            $this->buildPriceLine($timestamp),
            ($question === '' || $question === __(ChatConstants::DEFAULT_USER_QUESTION))
                ? $this->buildDefaultInsightPrompt($type, $persona)
                : $this->buildQuestionPrompt($question),

            $this->buildWritingStyleInstructions(),
        ]));
    }

    private function buildDefaultInsightPrompt(PromptType $type, PromptPersona $persona): string
    {
        return implode("\n", [
            'Task: Summarize insights from blockchain data.',
            '- Focus on: new, surprising, or non-obvious patterns.',
            "- Don't fabricate or repeat the raw data.",
            '- All values are in satoshis.',
            $this->getAdditionalTaskInstructions($type),
            $persona->instructions($type),

        ]);
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
- Keep the entire answer brief and focused on key points.
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
