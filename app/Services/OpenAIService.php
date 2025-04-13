<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
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
Write a short, casual, and punchy paragraph describing the following Bitcoin {$type}.
Split paragraphs if they have more than 50 words.
Consider:
- Sound like you're explaining it to non-crypto-experts — friendly and informal.
- Avoid saying repetitive information (eg "we've got a tx here with the ID...")
- The value inside "vin" is for inputs, and inside vout is for outputs, and the value is in sats
- 100 million sats = 1 BTC
- use Markdown format to remark the key points (style the response!)
- Avoid full hashes, use the first 10 chars instead (if needed at all)

Mention interesting facts like:
- anything odd, historic, or funny about it
- Mention what type of features are enabled (eg: RBF, Version, multisig, p2sh, OP_RETURN, coin join, consolidation, etc)

Now here’s the actual Bitcoin {$type} data to describe:
{$json}
PROMPT;

        $response = $this->http->withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $text = $response->json('choices.0.message.content');

        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }
}
