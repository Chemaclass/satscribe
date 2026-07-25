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
        $query = as_string($request->input('q'));

        if ($query === '') {
            return new JsonResponse(['status' => 'error', 'message' => 'Missing query'], Response::HTTP_BAD_REQUEST);
        }

        // Creating a chat demands a txid or a height; this route reaches the
        // same lookup and must demand the same, or the text lands unchecked in
        // the path of an outbound Blockstream URL.
        if (!PromptInput::isValid($query)) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Not a Bitcoin TXID, block hash or height'],
                Response::HTTP_BAD_REQUEST,
            );
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
