<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Translation\Translator;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Application\OpenAIFacade;
use Modules\OpenAI\Application\OpenAIService;
use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OpenAIFacadeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $mock = mock(Translator::class);
        $mock->shouldReceive('get')->andReturnArg(0);
        app()->instance('translator', $mock);
    }

    public function test_generate_text_delegates_to_service(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('json')->willReturnCallback(static fn (string $key) => match ($key) {
            'choices.0.message.content' => 'Sentence 1. Sentence 2',
            'error.message' => null,
            default => null,
        });

        $pending = $this->createMock(PendingRequest::class);
        $pending->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('withToken')
            ->willReturn($pending);

        $logger = self::createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $service = new OpenAIService(
            $http,
            $logger,
            new PersonaPromptBuilder('en'),
            self::createStub(PriceServiceInterface::class),
            now(),
            openAiApiKey: 'key',
            openAiModel: 'model',
        );
        $facade = new OpenAIFacade($service);

        $result = $facade->generateText($data, $input, PromptPersona::Educator, 'Question');

        $this->assertSame('Sentence 1.', $result);
    }
}
