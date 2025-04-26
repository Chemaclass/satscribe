<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Blockchain\BlockchainData;
use App\Data\PromptInput;
use App\Enums\PromptPersona;
use App\Enums\PromptType;
use App\Exceptions\OpenAIError;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final readonly class OpenAIService
{
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
                    'content' => $this->preparePrompt($data, $input->type, $persona, $question),
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
        $text = $this->trimToLastFullSentence($text);
        $this->logger->info("OpenAI generated description:\n".$text);

        return $text;
    }

    private function preparePrompt(
        BlockchainData $data,
        PromptType $type,
        PromptPersona $persona,
        string $question,
    ): string {
        $questionInstructions = $question ?: $this->defaultQuestionInstructions($type);

        $corePrompt = <<<PROMPT
Describe this Bitcoin {$type->value} using the following blockchain data:
{$data->toPrompt()}

Instructions:
- The values are in satoshis (1 satoshi = 0.00000001 BTC).
- Skip "Unknown" or anything that you are not certain about, avoid repeating information.
- If I asked you a question, you should answer it first, followed by a tldr of whatever you consider best.
{$questionInstructions}
PROMPT;

        return $this->wrapPromptWithPersona($corePrompt, $persona);
    }

    private function defaultQuestionInstructions(PromptType $type): string
    {
        $prompt = <<<TEXT
Use markdown formatting.
A paragraph should not be larger than 80 words.
If it's a historically important {$type->value}, mention it explicitly.
TEXT;

        if ($type === PromptType::Block) {
            $prompt .= <<<PROMPT
- If the block contains just one tx or something extraordinary occurs on it, say so.
PROMPT;
        }

        return $prompt;
    }

    private function wrapPromptWithPersona(string $prompt, PromptPersona $persona): string
    {
        return "{$persona->systemPrompt()}. End your response naturally, without cutting off mid-sentence. \n\n{$prompt}";
    }

    private function trimToLastFullSentence(string $text): string
    {
        // Match all ending sentence punctuation
        $matches = [];
        preg_match_all('/[.?!](?=\s|$)/u', $text, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && $matches[0] !== []) {
            $last = end($matches[0]);
            return mb_substr($text, 0, $last[1] + 1);
        }

        return $text; // fallback
    }
}
