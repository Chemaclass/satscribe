<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\BlockchainException;
use App\Exceptions\OpenAIError;
use App\Http\Requests\HomeIndexRequest;
use App\Services\HomeService;
use Illuminate\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class HomeController
{
    public function __construct(
        private HomeService $service,
        private LoggerInterface $logger,
    ) {
    }

    public function index(): View
    {
        return view('home', $this->service->getIndexData());
    }

    public function createChat(HomeIndexRequest $request): JsonResponse
    {
        $this->logger->info('Creating chat request', [
            'search' => $request->hasSearchInput() ? $request->getSearchInput() : null,
            'persona' => $request->getPersonaInput(),
            'ip' => $request->ip(),
        ]);

        try {
            $data = $this->service->createChat($request);
            $this->logger->info('Chat created successfully', [
                'chat_ulid' => $data['chatUlid'] ?? null,
            ]);
        } catch (BlockchainException|OpenAIError $e) {
            $this->logger->error('Failed to describe prompt result', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search' => $request->hasSearchInput() ? $request->getSearchInput() : null,
                'persona' => $request->getPersonaInput(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json($data);
    }
}
