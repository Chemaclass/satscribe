<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Carbon\Carbon;
use Modules\Payment\Application\CachedInvoiceValidator;
use Modules\Payment\Domain\AlbyClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CachedInvoiceValidatorTest extends TestCase
{
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

    /**
     * The cache is untyped storage, so presence of a key says nothing about
     * its type. These used to reach Carbon::parse() and isInvoicePaid(string)
     * unchecked and die with a TypeError instead of being treated as unusable.
     *
     * @param array<string, mixed> $cached
     */
    #[DataProvider('malformedInvoices')]
    public function test_returns_false_for_a_malformed_cache_entry(array $cached): void
    {
        $alby = self::createStub(AlbyClientInterface::class);
        $alby->method('isInvoicePaid')->willReturn(false);

        $validator = $this->newValidator($alby, Carbon::parse('2024-01-01 00:00:00'));

        $this->assertFalse($validator->isValidCachedInvoice($cached));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function malformedInvoices(): iterable
    {
        $valid = [
            'payment_hash' => 'hash',
            'payment_request' => 'ln',
            'created_at' => '2023-12-31 23:59:30',
            'expiry' => 60,
        ];

        yield 'non-string hash' => [['payment_hash' => ['hash']] + $valid];
        yield 'non-string request' => [['payment_request' => 123] + $valid];
        yield 'non-string created_at' => [['created_at' => ['now']] + $valid];
        yield 'non-numeric expiry' => [['expiry' => 'soon'] + $valid];
    }

    private function newValidator(AlbyClientInterface $alby, Carbon $now): CachedInvoiceValidator
    {
        $logger = self::createStub(LoggerInterface::class);

        return new CachedInvoiceValidator($alby, $logger, $now);
    }
}
