<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repository;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * Create a new payment record.
     *
     * @param  array{
     *     tracking_id:?string,
     *     chat_id:?int,
     *     payment_hash:string,
     *     memo:string,
     *     amount:int,
     *     status:string,
     *     failure_reason:?string,
     * } $data
     */
    public function create(array $data): Payment;
}
