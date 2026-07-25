<?php

declare(strict_types=1);

namespace Modules\OpenAI\Application;

use App\Models\Chat;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Generator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Domain\Data\ModelSelection;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\OpenAI\Domain\ProviderRegistryInterface;
use Modules\Shared\Domain\Chat\ChatConstants;
use Modules\Shared\Domain\Chat\SentenceTrimmer;
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
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(
        private HttpClientInterface $http,
        private HttpFactory $httpFactory,
        private CacheRepository $cache,
        private LoggerInterface $logger,
        private PersonaPromptBuilder $promptBuilder,
        // Deliberately the narrow price contract rather than BlockchainFacade:
        // routing prices through the facade forces every unrelated consumer of
        // it — the UTXO tracer's doubles among them — to satisfy four price
        // methods they never call.
        private PriceServiceInterface $priceService,
        private CarbonInterface $now,
        private ProviderRegistryInterface $registry,
    ) {
    }

    public function generateText(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat = null,
        string $additionalContext = '',
        ?ModelSelection $selection = null,
    ): string {
        // Follow-ups have historically used their own model, and no selection
        // means "keep doing exactly what the config says".
        $selection ??= $chat instanceof Chat
            ? $this->registry->defaultFollowupSelection()
            : $this->registry->defaultSelection();

        // Only cache initial requests (no chat history)
        $cacheKey = $chat instanceof Chat ? null : $this->buildCacheKey($input, $persona, $question, $selection);

        if ($cacheKey !== null) {
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                $this->logger->debug('Returning cached OpenAI response', ['key' => $cacheKey]);

                return $cached;
            }
        }

        $text = $this->callOpenAI($data, $input, $persona, $question, $chat, $additionalContext, $selection);

        if ($cacheKey !== null) {
            $this->cache->put($cacheKey, $text, self::CACHE_TTL_SECONDS);
        }

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
        ?ModelSelection $selection = null,
    ): Generator {
        $selection ??= $this->registry->defaultSelection();

        $this->logger->debug('Calling OpenAI API with streaming', [
            ...$selection->toLogContext(),
            'persona' => $persona->value,
        ]);

        $response = $this->httpFactory
            ->withToken($selection->apiKey())
            ->withOptions(['stream' => true])
            ->timeout(60)
            ->post($selection->endpoint(), [
                'model' => $selection->model,
                'messages' => $this->buildMessages($data, $input, $persona, $question, $chat, $additionalContext),
                'max_tokens' => $persona->maxTokens(),
                'stream' => true,
            ]);

        if ($response->failed()) {
            $this->logger->error('OpenAI API streaming request failed', [
                ...$selection->toLogContext(),
                'status' => $response->status(),
            ]);

            throw new OpenAIError('OpenAI API streaming request failed');
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

    private function callOpenAI(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat,
        string $additionalContext,
        ModelSelection $selection,
    ): string {
        $this->logger->debug('Calling OpenAI API', [
            ...$selection->toLogContext(),
            'persona' => $persona->value,
            'is_followup' => $chat instanceof Chat,
        ]);

        $response = $this->http->withToken($selection->apiKey())
            ->post($selection->endpoint(), [
                'model' => $selection->model,
                'messages' => $this->buildMessages($data, $input, $persona, $question, $chat, $additionalContext),
                'max_tokens' => $persona->maxTokens(),
            ]);

        if ($response->failed()) {
            $this->logger->error('OpenAI API request failed', [
                ...$selection->toLogContext(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new OpenAIError('OpenAI API request failed');
        }

        if ($error = $response->json('error.message')) {
            $this->logger->error('OpenAI API responded with an error', [
                ...$selection->toLogContext(),
                'error' => $error,
                'status' => $response->status(),
            ]);
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $text = SentenceTrimmer::toLastFullSentence($text);
        $this->logger->debug('OpenAI description generation worked', ['length' => strlen($text)]);

        return $text;
    }

    /**
     * Assembles the chat completion payload shared by the streaming and
     * non-streaming paths. Keeping it in one place is what prevents the two
     * from drifting apart again.
     *
     * @return array<int, array<string, string>>
     */
    private function buildMessages(
        BlockchainData $data,
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ?Chat $chat,
        string $additionalContext,
    ): array {
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

        return [
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
    }

    /**
     * The model is part of the key: two providers answering the same question
     * are two different answers, and must not share a cache entry.
     */
    private function buildCacheKey(
        PromptInput $input,
        PromptPersona $persona,
        string $question,
        ModelSelection $selection,
    ): string {
        $questionHash = md5($question);

        return "openai:{$selection->provider->id()}:{$selection->model}:{$input->type->value}:{$input->text}:{$persona->value}:{$questionHash}";
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

}
