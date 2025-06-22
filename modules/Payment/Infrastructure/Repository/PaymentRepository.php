<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repository;

use App\Models\Payment;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;

final readonly class PaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}
