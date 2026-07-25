<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Payment\Domain\Data\AlbySettleWebhookPayload;
use Modules\Payment\Domain\Exception\InvalidAlbyWebhookPayloadException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AlbySettleWebhookPayloadTest extends TestCase
{
    public function test_from_array_reads_a_well_formed_payload(): void
    {
        $payload = AlbySettleWebhookPayload::fromArray([
            'payment_hash' => 'hash',
            'type' => 'incoming',
            'state' => 'SETTLED',
            'memo' => 'Zap',
            'amount' => 150,
            'ignored' => 'extra',
        ]);

        self::assertSame('hash', $payload->paymentHash);
        self::assertSame(150, $payload->amount);
    }

    /**
     * The body is attacker-controlled up to Svix signature verification, so a
     * field of the wrong type must raise the domain exception the webhook
     * controller handles — not a TypeError that becomes a 500.
     *
     * @param array<string, mixed> $data
     */
    #[DataProvider('malformedPayloads')]
    public function test_from_array_rejects_a_malformed_payload(array $data): void
    {
        $this->expectException(InvalidAlbyWebhookPayloadException::class);

        AlbySettleWebhookPayload::fromArray($data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function malformedPayloads(): iterable
    {
        $valid = [
            'payment_hash' => 'hash',
            'type' => 'incoming',
            'state' => 'SETTLED',
            'memo' => 'Zap',
            'amount' => 150,
        ];

        foreach (array_keys($valid) as $field) {
            $missing = $valid;
            unset($missing[$field]);

            yield "missing {$field}" => [$missing];
        }

        yield 'non-string payment_hash' => [['payment_hash' => ['hash']] + $valid];
        yield 'non-string type' => [['type' => 1] + $valid];
        yield 'non-string state' => [['state' => false] + $valid];
        yield 'non-string memo' => [['memo' => ['Zap']] + $valid];
        yield 'non-int amount' => [['amount' => 'one fifty'] + $valid];
    }
}
