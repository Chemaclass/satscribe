<?php

declare(strict_types=1);

namespace Modules\Blockchain\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Modules\Blockchain\Domain\BlockchainFacadeInterface;
use Modules\Blockchain\Domain\Exception\BlockchainException;
use Modules\Shared\Domain\Data\Chat\PromptInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class PrefetchController
{
    public function __construct(
        private BlockchainFacadeInterface $blockchainFacade,
    ) {
    }

    public function prefetch(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');

        if ($query === '') {
            return new JsonResponse(['status' => 'error', 'message' => 'Missing query'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $input = PromptInput::fromRaw($query);
            $this->blockchainFacade->getBlockchainData($input);

            return new JsonResponse(['status' => 'ok', 'type' => $input->type->value]);
        } catch (BlockchainException $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
