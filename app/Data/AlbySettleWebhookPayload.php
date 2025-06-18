<?php

declare(strict_types=1);

namespace App\Data;

use App\Exceptions\InvalidAlbyWebhookPayloadException;

final readonly class AlbySettleWebhookPayload
{
    public function __construct(
        public string $paymentHash,
        public string $type,
        public string $state,
        public string $memo,
        public int $amount,
    ) {
    }

    /**
     * @param  array{payment_hash:string,type:string,state:string,memo:string,amount:int}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentHash: $data['payment_hash'] ?? throw InvalidAlbyWebhookPayloadException::missing('payment_hash'),
            type: $data['type'] ?? throw InvalidAlbyWebhookPayloadException::missing('type'),
            state: $data['state'] ?? throw InvalidAlbyWebhookPayloadException::missing('state'),
            memo: $data['memo'] ?? throw InvalidAlbyWebhookPayloadException::missing('memo'),
            amount: $data['amount'] ?? throw InvalidAlbyWebhookPayloadException::missing('amount'),
        );
    }

    public function toArray(): array
    {
        return [
            'payment_hash' => $this->paymentHash,
            'type' => $this->type,
            'state' => $this->state,
            'memo' => $this->memo,
            'amount' => $this->amount,
        ];
    }
}
