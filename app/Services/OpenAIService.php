<?php
declare(strict_types=1);

namespace App\Services;

use App\Data\BlockchainData;
use Illuminate\Support\Facades\Http;

final class OpenAIService
{
    public function generateText(BlockchainData $data, string $type): ?string
    {
        $prompt = "Write a paragraph describing this Bitcoin $type:\n\n".json_encode($data, JSON_PRETTY_PRINT);

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);

        return $response->json('choices.0.message.content');
    }
}
