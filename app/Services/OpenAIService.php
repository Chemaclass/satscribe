<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class OpenAIService
{
    private const LIMIT_TOKENS_PER_REQUEST = 50_000;

    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
    ) {
    }

    public function generateText(BlockchainData $data, string $type, string $question = ''): ?string
    {
        $response = $this->http->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->preparePrompt($data, $type, $question),
                    ],
                ],
            ]);

        if ($error = $response->json('error.message')) {
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }

    private function preparePrompt(BlockchainData $data, string $type, string $question): string
    {
        $json = json_encode($this->compactBlockchainData($data->toArray()), JSON_UNESCAPED_SLASHES);

        $defaultQuestion = <<<EOT
Categorize wallet types and features: multisig, P2SH, OP_RETURN, RBF, CoinJoin, etc.
Mention anything unusual (batching, dust, consolidation) in a separate paragraph.
EOT;

        $questionPart = $question ?: $defaultQuestion;

        $prompt = <<<EOT
Use **Markdown** to highlight key info.
Write a concise and accessible paragraph describing the following Bitcoin {$type}.

{$questionPart}

Guidelines:
- Inputs ("vin") = senders, Outputs ("vout") = recipients. Values are in sats (100,000,000 sats = 1 BTC)
- Keep it short. Use multiple paragraphs if needed. If the response exceeds 40 words, break it into smaller paragraphs.
- Max answered to 100 tokens.

Here's a condensed view of the Bitcoin {$type} data:
{$json}
EOT;

        return $this->truncateByApproxTokens($prompt, self::LIMIT_TOKENS_PER_REQUEST);
    }

    private function compactBlockchainData(array $data): array
    {
        // For transactions
        if (isset($data['vin']) || isset($data['vout'])) {
            return [
                'txid' => $data['txid'] ?? null,
                'inputs' => array_map(fn($vin) => [
                    'addr' => $vin['prevout']['scriptpubkey_address'] ?? null,
                    'val' => $vin['prevout']['value'] ?? null,
                ], $data['vin'] ?? []),
                'outputs' => array_map(fn($vout) => [
                    'addr' => $vout['scriptpubkey_address'] ?? null,
                    'val' => $vout['value'] ?? null,
                ], $data['vout'] ?? []),
                'fee' => $data['fee'] ?? null,
                'size' => $data['size'] ?? null,
            ];
        }

        // For blocks
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

    private function truncateByApproxTokens(string $text, int $maxTokens): string
    {
        // Rough tokenizer: splits by words and punctuation
        $words = preg_split('/(?=\b)|(?<=\b)/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $tokens = 0;
        $output = '';

        foreach ($words as $word) {
            // Heuristic: assume 1.3 tokens per word/punctuation
            $estimated = ceil(strlen($word) / 4); // rough OpenAI token estimate
            if ($tokens + $estimated > $maxTokens) {
                break;
            }

            $tokens += $estimated;
            $output .= $word;
        }

        return $output;
    }
}
