<?php

declare(strict_types=1);

namespace Tests\Unit;

use Carbon\Carbon;
use Modules\Payment\Application\CachedInvoiceValidator;
use Modules\Payment\Domain\AlbyClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CachedInvoiceValidatorTest extends TestCase
{
    private function newValidator(AlbyClientInterface $alby, Carbon $now): CachedInvoiceValidator
    {
        $logger = self::createStub(LoggerInterface::class);

        return new CachedInvoiceValidator($alby, $logger, $now);
    }

    public function test_returns_true_when_unexpired_and_unpaid(): void
    {
        $alby = self::createStub(AlbyClientInterface::class);
        $alby->method('isInvoicePaid')->willReturn(false);

        $now = Carbon::parse('2024-01-01 00:00:00');
        $validator = $this->newValidator($alby, $now);

        $cached = [
            'payment_hash' => 'hash',
            'payment_request' => 'ln',
            'created_at' => $now->copy()->subSeconds(30)->toDateTimeString(),
            'expiry' => 60,
        ];

        $this->assertTrue($validator->isValidCachedInvoice($cached));
    }

    public function test_returns_false_when_expired(): void
    {
        $alby = self::createStub(AlbyClientInterface::class);
        $alby->method('isInvoicePaid')->willReturn(false);

        $now = Carbon::parse('2024-01-01 00:01:01');
        $validator = $this->newValidator($alby, $now);

        $cached = [
            'payment_hash' => 'hash',
            'payment_request' => 'ln',
            'created_at' => $now->copy()->subSeconds(120)->toDateTimeString(),
            'expiry' => 60,
        ];

        $this->assertFalse($validator->isValidCachedInvoice($cached));
    }

    public function test_returns_false_when_invoice_paid(): void
    {
        $alby = self::createStub(AlbyClientInterface::class);
        $alby->method('isInvoicePaid')->willReturn(true);

        $now = Carbon::parse('2024-01-01 00:00:00');
        $validator = $this->newValidator($alby, $now);

        $cached = [
            'payment_hash' => 'hash',
            'payment_request' => 'ln',
            'created_at' => $now->copy()->subSeconds(30)->toDateTimeString(),
            'expiry' => 60,
        ];

        $this->assertFalse($validator->isValidCachedInvoice($cached));
    }
}
