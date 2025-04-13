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
Split in two paragraph if it has more than 60 words.
Make it sound like you're explaining it to non-crypto-experts — friendly and informal.
Mention interesting facts like:
- if it's an old block, a huge transfer, low/high fees, etc
- anything odd, historic, or funny about it
- the value inside "vin" is for inputs, and inside vout is for outputs, and the value is in sats
- 100 million sats = 1 BTC
- Avoid full hashes, use the first 10 chars instead (if needed at all)
- Mention what type of features has enabled (like RBF, Version, multisig, p2sh, op_return, fake pubkey, coinjoin, consolidation, etc)

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
