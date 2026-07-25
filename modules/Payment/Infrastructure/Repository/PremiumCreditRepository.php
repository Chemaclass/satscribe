<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repository;

use App\Models\PremiumCreditEntry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Domain\PremiumCreditsInterface;

final readonly class PremiumCreditRepository implements PremiumCreditsInterface
{
    public function balanceFor(string $npub): int
    {
        return (int) PremiumCreditEntry::where('npub', $npub)->sum('delta');
    }

    public function grantPack(string $npub, string $paymentHash, int $messages): void
    {
        try {
            PremiumCreditEntry::create([
                'npub' => $npub,
                'delta' => $messages,
                'reason' => PremiumCreditEntry::REASON_PURCHASE,
                'payment_hash' => $paymentHash,
            ]);
        } catch (QueryException) {
            // payment_hash is unique, so a redelivered settlement collides here
            // rather than granting a second pack for one payment.
        }
    }

    public function spendOne(string $npub): bool
    {
        // Serialised so two requests cannot both read the last credit and both
        // decide they may spend it.
        return DB::transaction(function () use ($npub): bool {
            $balance = (int) PremiumCreditEntry::where('npub', $npub)
                ->lockForUpdate()
                ->sum('delta');

            if ($balance < 1) {
                return false;
            }

            PremiumCreditEntry::create([
                'npub' => $npub,
                'delta' => -1,
                'reason' => PremiumCreditEntry::REASON_SPEND,
            ]);

            return true;
        });
    }
}
