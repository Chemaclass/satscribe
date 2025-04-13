<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use Illuminate\Http\Client\Factory as HttpClient;

final readonly class OpenAIService
{
    public function __construct(
        private HttpClient $http
    ) {
    }

    public function generateText(BlockchainData $data, string $type): ?string
    {
        $json = json_encode($data->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $example = <<<TEXT
So, check it out: this is the very first Bitcoin block, known as the genesis block! Mined on January 3, 2009, it has a unique hash starting with a ton of zeros, which is pretty cool. It contains a single transaction that rewards the miner with 50 BTC, marking the birth of the Bitcoin era. The miner didn’t pay any transaction fees because, well, it's the first block! It’s a significant piece of crypto history, packed into just 134 bytes.
TEXT;

        $prompt = <<<PROMPT
You're a fun and informal narrator for Bitcoin geeks. Write a **short, casual, and punchy** paragraph describing the following Bitcoin {$type}. Make it sound like you're explaining it to a crypto-curious friend — friendly, energetic, and informal.

Try to mention interesting facts like:
- how much BTC was sent or mined
- how many transactions or inputs/outputs there were
- if it's an old block, a huge transfer, low/high fees, etc.
- anything odd, historic, or funny about it

Here's an example of the tone to use:

{$example}

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

        return $response->json('choices.0.message.content');
    }
}
