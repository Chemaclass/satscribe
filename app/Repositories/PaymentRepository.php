<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;

final readonly class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * @param array{tracking_id:?string,payment_hash:string,memo:string,amount:int} $data
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }
}
