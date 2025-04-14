<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
Write a concise and accessible paragraph describing the following Bitcoin {$type}.
Use a clear, easy-to-understand tone suitable for a general audience.
If the response exceeds 50 words, break it into smaller paragraphs.

Guidelines:
- Explain concepts in a way that's understandable to readers without deep technical knowledge of Bitcoin.
- Avoid redundancy or unnecessary restatements of obvious data points.
- Treat "vin" as inputs and "vout" as outputs. Values are in satoshis (sats).
- 100,000,000 sats equals 1 BTC.
- Avoid including full hashes; shorten to the first 10 characters if necessary.

If relevant, highlight:
- Unusual or noteworthy characteristics
- Use of features like RBF, multisig, P2SH, OP_RETURN, CoinJoin, consolidation, etc.

Use Markdown to emphasize key elements or structure the output.

Here is the Bitcoin {$type} data to describe:
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
