<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class OpenAIService
{
    private const TRIMMED_IO_LIMIT = 5;

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
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }

    private function preparePrompt(
        BlockchainData $data,
        PromptType $type,
        string $question,
        PromptPersona $persona,
    ): string {
        $condensedData = $this->compactBlockchainData($data->toArray());
        $json = (string) json_encode($condensedData, JSON_UNESCAPED_SLASHES);

        $questionPart = $question ?: $this->defaultQuestionInstructions($type);

        $corePrompt = <<<PROMPT
Describe this Bitcoin {$type->value} using the following blockchain data:

{$json}

Instructions:
{$questionPart}

PROMPT;

        return $this->wrapPromptWithPersona($corePrompt, $persona);
    }

    private function defaultQuestionInstructions(PromptType $type): string
    {
        return <<<TEXT
Use markdown formatting.
If applicable, include wallet features like:
- Multi-signature, P2PK, P2PKH, P2SH, etc.
- CoinJoin, Segwit, Taproot
If it's a historically important {$type->value}, mention it explicitly.
TEXT;
    }

    private function wrapPromptWithPersona(string $prompt, PromptPersona $persona): string
    {
        return "{$persona->systemPrompt()}\n\n{$prompt}";
    }

    private function compactBlockchainData(array $data): array
    {
        if (!isset($data['vin']) && !isset($data['vout'])) {
            return [
                'height' => $data['height'] ?? null,
                'tx_count' => $data['tx_count'] ?? null,
                'miner' => $data['extras']['miner'] ?? null,
                'reward' => $data['extras']['reward'] ?? null,
                'size' => $data['size'] ?? null,
                'weight' => $data['weight'] ?? null,
                'timestamp' => $data['timestamp'] ?? null,
            ];
        }

        return [
            'txid' => $data['txid'] ?? null,
            'inputs' => $this->limitItems($data['vin'] ?? [], self::TRIMMED_IO_LIMIT, fn($vin) => [
                'addr' => $vin['prevout']['scriptpubkey_address'] ?? null,
                'val' => $vin['prevout']['value'] ?? null,
            ]),
            'outputs' => $this->limitItems($data['vout'] ?? [], self::TRIMMED_IO_LIMIT, fn($vout) => [
                'addr' => $vout['scriptpubkey_address'] ?? null,
                'val' => $vout['value'] ?? null,
            ]),
            'fee' => $data['fee'] ?? null,
            'size' => $data['size'] ?? null,
        ];
    }

    /**
     * Limit list size and summarize if trimmed.
     */
    private function limitItems(array $items, int $limit, callable $map): array
    {
        $sliced = array_slice($items, 0, $limit);
        $mapped = array_map($map, $sliced);

        if (count($items) > $limit) {
            $mapped[] = ['_summary' => sprintf('+%d more omitted', count($items) - $limit)];
        }

        return $mapped;
    }
}
