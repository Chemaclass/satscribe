<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Data;

use Modules\Payment\Domain\Exception\InvalidAlbyWebhookPayloadException;

use function is_int;
use function is_string;

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
     * Decoded webhook body. It is attacker-controlled up to Svix signature
     * verification, and presence alone says nothing about type: a field of the
     * wrong type used to reach the constructor and fail as a TypeError, which
     * the webhook controller turns into a 500 rather than a rejected payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentHash: self::string($data, 'payment_hash'),
            type: self::string($data, 'type'),
            state: self::string($data, 'state'),
            memo: self::string($data, 'memo'),
            amount: self::int($data, 'amount'),
        );
    }

    /**
     * @return array{
     *     payment_hash: string,
     *     type: string,
     *     state: string,
     *     memo: string,
     *     amount: int,
     * }
     */
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

    /**
     * @param  array<string, mixed>  $data
     */
    private static function string(array $data, string $field): string
    {
        $value = $data[$field] ?? throw InvalidAlbyWebhookPayloadException::missing($field);

        if (!is_string($value)) {
            throw InvalidAlbyWebhookPayloadException::malformed($field, 'a string');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function int(array $data, string $field): int
    {
        $value = $data[$field] ?? throw InvalidAlbyWebhookPayloadException::missing($field);

        if (!is_int($value)) {
            throw InvalidAlbyWebhookPayloadException::malformed($field, 'an integer');
        }

        return $value;
    }
}
