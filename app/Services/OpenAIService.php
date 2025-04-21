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
    private const LIMIT_TOKENS_PER_REQUEST = 50_000;
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
        string $question = '',
        ?PromptPersona $persona = null,
    ): string {
        $payload = [
            'model' => $this->openAiModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->preparePrompt($data, $input->type, $question, $persona),
                ],
            ],
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
        ?PromptPersona $persona = null,
    ): string {
        $condensedData = $this->compactBlockchainData($data->toArray());
        $json = (string) json_encode($condensedData, JSON_UNESCAPED_SLASHES);

        $questionPart = $question ?: <<<TEXT
Use markdown formatting.
If there would be any, then include wallet features, for example:
- Multi-signature
- P2PK
- P2PKH
- P2SH
- P2MS
- P2WPKH
- P2WSH
- P2TR
- OP_RETURN
- RBF
- CoinJoin
- Segwit
- Taproot
- Or if it is an important historical {$type->value}, mention it explicitly.
TEXT;

        $corePrompt = <<<PROMPT
Describe this Bitcoin {$type->value} using the following blockchain data:

{$json}

Instructions:
{$questionPart}

Max output 300 words.
PROMPT;

        $finalPrompt = $this->wrapPromptWithPersona($corePrompt, $persona);

        return $this->truncateByApproxTokens($finalPrompt, self::LIMIT_TOKENS_PER_REQUEST);
    }

    private function wrapPromptWithPersona(string $prompt, ?PromptPersona $persona): string
    {
        return match ($persona) {
            PromptPersona::Educator => <<<TEXT
You are an experienced Bitcoin educator. Break things down in simple terms, as if explaining to someone new to Bitcoin. Use analogies or examples when helpful.

{$prompt}
TEXT,
            PromptPersona::Developer => <<<TEXT
You are a Bitcoin developer and technical analyst. Focus on technical accuracy, relevant scripts, and protocol-level behavior. Mention transaction types and byte-level detail where appropriate.

{$prompt}
TEXT,
            PromptPersona::Storyteller => <<<TEXT
You are a storyteller who explains Bitcoin history and behavior in engaging narratives. Weave context and insights into a short story or real-world metaphor.

{$prompt}
TEXT,
            default => <<<TEXT
You are an expert Bitcoin educator and technical writer.
Provide a clear, beginner-friendly description of this Bitcoin transaction or block.

{$prompt}
TEXT,
        };
    }

    private function compactBlockchainData(array $data): array
    {
        if (isset($data['vin']) || isset($data['vout'])) {
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

    /**
     * Token estimation via simple heuristic (approximation only).
     */
    private function truncateByApproxTokens(string $text, int $maxTokens): string
    {
        $words = preg_split('/(?=\b)|(?<=\b)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $tokens = 0;
        $output = '';

        foreach ($words as $word) {
            $estimated = ceil(strlen($word) / 4); // very rough estimate
            if ($tokens + $estimated > $maxTokens) {
                break;
            }

            $tokens += $estimated;
            $output .= $word;
        }

        return $output;
    }
}
