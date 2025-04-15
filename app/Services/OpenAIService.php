<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class OpenAIService
{
    public function __construct(
        private HttpClient $http,
        private LoggerInterface $logger,
    ) {
    }

    public function generateText(BlockchainData $data, string $type): ?string
    {
        $json = json_encode($data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $prompt = <<<PROMPT
Use **Markdown** to highlight key info.

Write a concise and accessible paragraph describing the following Bitcoin {$type}.
Use a clear, easy-to-understand tone suitable for a general audience.

Categorize by which wallet type or enabled features like multisig, P2SH, OP_RETURN, RBF, CoinJoin, etc

Guidelines:
- Inputs ("vin") = senders, Outputs ("vout") = recipients. Values are in sats (100,000,000 sats = 1 BTC)
- Keep it short. Use multiple paragraphs if needed. If the response exceeds 50 words, break it into smaller paragraphs.
- Note anything unusual: batching, dust, consolidation, etc, remark it at the end in an extra paragraph

Here's the Bitcoin {$type}:
{$json}
PROMPT;

        $response = $this->http->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($error = $response->json('error.message')) {
            throw new OpenAIError($error);
        }

        $text = $response->json('choices.0.message.content');
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }
}
