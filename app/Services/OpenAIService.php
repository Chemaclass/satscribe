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
        $prompt = "Write a very short paragraph describing informally this Bitcoin $type:\n\n".json_encode($data->toArray(), JSON_PRETTY_PRINT);

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
