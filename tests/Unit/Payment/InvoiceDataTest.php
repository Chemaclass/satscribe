<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Modules\Shared\Domain\Data\Payment\InvoiceData;
use PHPUnit\Framework\TestCase;

/**
 * toArray() is the Alby `POST /invoices` body. AlbyClient used to build that
 * body by hand, so these pin the two shapes it produced: the optional
 * description fields are omitted when unset rather than sent as null, which
 * Alby rejects.
 */
final class InvoiceDataTest extends TestCase
{
    public function test_to_array_omits_the_unset_description_fields(): void
    {
        $invoice = new InvoiceData(amount: 150, memo: 'Zap #abc', expiry: 300);

        self::assertSame([
            'amount' => 150,
            'memo' => 'Zap #abc',
            'expiry' => 300,
        ], $invoice->toArray());
    }

    public function test_to_array_includes_both_description_fields_when_set(): void
    {
        $invoice = new InvoiceData(
            amount: 210,
            memo: 'Zap #def',
            description: 'a description',
            descriptionHash: 'a-hash',
            expiry: 600,
        );

        self::assertSame([
            'amount' => 210,
            'memo' => 'Zap #def',
            'description' => 'a description',
            'description_hash' => 'a-hash',
            'expiry' => 600,
        ], $invoice->toArray());
    }
}
