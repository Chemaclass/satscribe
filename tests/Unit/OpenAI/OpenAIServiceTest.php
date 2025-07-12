<?php

declare(strict_types=1);

namespace Tests\Unit\OpenAI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Translation\Translator;
use Modules\Blockchain\Domain\PriceServiceInterface;
use Modules\OpenAI\Application\OpenAIService;
use Modules\OpenAI\Application\PersonaPromptBuilder;
use Modules\OpenAI\Domain\Exception\OpenAIError;
use Modules\Shared\Domain\Data\Blockchain\BlockchainData;
use Modules\Shared\Domain\Data\Blockchain\BlockData;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Modules\Shared\Domain\Enum\Chat\PromptPersona;
use Modules\Shared\Domain\Enum\Chat\PromptType;
use Modules\Shared\Domain\HttpClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OpenAIServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $mock = mock(Translator::class);
        $mock->shouldReceive('get')->andReturnArg(0);
        app()->instance('translator', $mock);
    }

    public function test_returns_trimmed_text(): void
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
            ->with('api-key')
            ->willReturn($pending);

        $logger = $this->createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $priceService = $this->createStub(PriceServiceInterface::class);
        $priceService->method('getBtcPriceUsdAt')->willReturn(10000.0);
        $priceService->method('getBtcPriceEurAt')->willReturn(9000.0);
        $priceService->method('getCurrentBtcPriceUsd')->willReturn(30000.0);
        $priceService->method('getCurrentBtcPriceEur')->willReturn(27000.0);

        $service = new OpenAIService(
            $http,
            $logger,
            new PersonaPromptBuilder('en'),
            $priceService,
            openAiApiKey: 'api-key',
            openAiModel: 'model',
        );

        $result = $service->generateText($data, $input, PromptPersona::Developer, 'Question');

        $this->assertSame('Sentence 1.', $result);
    }

    public function test_throws_on_failed_response(): void
    {
        $response = $this->createConfiguredMock(Response::class, [
            'failed' => true,
            'status' => 500,
            'body' => 'error',
        ]);

        $pending = $this->createMock(PendingRequest::class);
        $pending->method('post')->willReturn($response);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('withToken')->willReturn($pending);

        $logger = $this->createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $priceService = $this->createStub(PriceServiceInterface::class);
        $priceService->method('getBtcPriceUsdAt')->willReturn(10000.0);
        $priceService->method('getBtcPriceEurAt')->willReturn(9000.0);
        $priceService->method('getCurrentBtcPriceUsd')->willReturn(30000.0);
        $priceService->method('getCurrentBtcPriceEur')->willReturn(27000.0);

        $service = new OpenAIService(
            $http,
            $logger,
            new PersonaPromptBuilder('en'),
            $priceService,
            openAiApiKey: 'api-key',
            openAiModel: 'model',
        );

        $this->expectException(OpenAIError::class);

        $service->generateText($data, $input, PromptPersona::Developer, 'Question');
    }

    public function test_throws_on_error_message(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('json')->willReturnCallback(static fn (string $key) => match ($key) {
            'error.message' => 'bad request',
            default => null,
        });

        $pending = $this->createMock(PendingRequest::class);
        $pending->method('post')->willReturn($response);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('withToken')->willReturn($pending);

        $logger = $this->createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $priceService = $this->createStub(PriceServiceInterface::class);
        $priceService->method('getBtcPriceUsdAt')->willReturn(10000.0);
        $priceService->method('getBtcPriceEurAt')->willReturn(9000.0);
        $priceService->method('getCurrentBtcPriceUsd')->willReturn(30000.0);
        $priceService->method('getCurrentBtcPriceEur')->willReturn(27000.0);

        $service = new OpenAIService(
            $http,
            $logger,
            new PersonaPromptBuilder('en'),
            $priceService,
            openAiApiKey: 'api-key',
            openAiModel: 'model',
        );

        $this->expectException(OpenAIError::class);

        $service->generateText($data, $input, PromptPersona::Developer, 'Question');
    }

    public function test_includes_price_in_prompt(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('failed')->willReturn(false);
        $response->method('json')->willReturnCallback(static fn (string $key) => match ($key) {
            'choices.0.message.content' => 'Done',
            'error.message' => null,
            default => null,
        });

        $captured = [];
        $pending = $this->createMock(PendingRequest::class);
        $pending->expects($this->once())
            ->method('post')
            ->with('https://api.openai.com/v1/chat/completions', $this->callback(static function ($body) use (&$captured) {
                $captured = $body['messages'];
                return true;
            }))
            ->willReturn($response);

        $http = $this->createMock(HttpClientInterface::class);
        $http->method('withToken')->willReturn($pending);

        $logger = $this->createStub(LoggerInterface::class);

        $block = new BlockData('h', height: 1, merkleRoot: 'm');
        $data = BlockchainData::forBlock($block);
        $input = new PromptInput(PromptType::Block, '1');

        $priceService = $this->createStub(PriceServiceInterface::class);
        $priceService->method('getBtcPriceUsdAt')->willReturn(25000.0);
        $priceService->method('getBtcPriceEurAt')->willReturn(23000.0);
        $priceService->method('getCurrentBtcPriceUsd')->willReturn(30000.0);
        $priceService->method('getCurrentBtcPriceEur')->willReturn(27000.0);

        $service = new OpenAIService(
            $http,
            $logger,
            new PersonaPromptBuilder('en'),
            $priceService,
            openAiApiKey: 'api-key',
            openAiModel: 'model',
        );

        $service->generateText($data, $input, PromptPersona::Developer, '');

        $this->assertNotEmpty($captured);
        $this->assertStringContainsString(
            '1 BTC was about $25,000 USD or €23,000 EUR. Today it is about $30,000 USD or €27,000 EUR.',
            $captured[2]['content'],
        );
    }
}
