<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Modules\Payment\Domain\ConfirmPremiumPackActionInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Modules\Payment\Domain\PremiumPackInvoiceInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class PremiumPackController
{
    public function __construct(
        private PremiumPackInvoiceInterface $packInvoice,
        private PremiumCreditsInterface $credits,
        private ConfirmPremiumPackActionInterface $confirmPack,
    ) {
    }

    public function balance(): JsonResponse
    {
        $npub = nostr_pubkey();

        return response()->json([
            'balance' => $npub === null ? 0 : $this->credits->balanceFor($npub),
            'pack_sats' => config_int('services.premium.pack_sats'),
            'pack_messages' => config_int('services.premium.pack_messages'),
        ]);
    }

    public function buy(): JsonResponse
    {
        $npub = nostr_pubkey();

        // Credit follows the Nostr identity, so there is nowhere to put a pack
        // bought without one.
        if ($npub === null) {
            return response()->json(
                ['error' => 'Log in with Nostr first, so your credit follows you between devices.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        return response()->json([
            'invoice' => $this->packInvoice->issueFor($npub),
            'pack_messages' => config_int('services.premium.pack_messages'),
        ]);
    }

    public function status(string $paymentHash): JsonResponse
    {
        $npub = nostr_pubkey() ?? '';

        return response()->json([
            'paid' => $this->confirmPack->execute($paymentHash, $npub),
            'balance' => $npub === '' ? 0 : $this->credits->balanceFor($npub),
        ]);
    }
}
