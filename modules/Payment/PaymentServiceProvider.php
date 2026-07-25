<?php

declare(strict_types=1);

namespace Modules\Payment;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\AlbyClient;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Application\CachedInvoiceValidator;
use Modules\Payment\Application\ConfirmInvoicePaymentAction;
use Modules\Payment\Application\ConfirmPremiumPackAction;
use Modules\Payment\Application\PaywallInvoiceIssuer;
use Modules\Payment\Application\PremiumAccess;
use Modules\Payment\Application\PremiumPackInvoice;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Modules\Payment\Domain\ConfirmInvoicePaymentActionInterface;
use Modules\Payment\Domain\ConfirmPremiumPackActionInterface;
use Modules\Payment\Domain\PremiumAccessInterface;
use Modules\Payment\Domain\PremiumCreditsInterface;
use Modules\Payment\Domain\PremiumPackInvoiceInterface;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Repository\PaymentRepository;
use Modules\Payment\Infrastructure\Repository\PremiumCreditRepository;
use Modules\Shared\Domain\RateLimit\PaywallInvoiceIssuerInterface;
use Override;

final class PaymentServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public $singletons = [
        AlbyClientInterface::class => AlbyClient::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        CachedInvoiceValidatorInterface::class => CachedInvoiceValidator::class,
        PaywallInvoiceIssuerInterface::class => PaywallInvoiceIssuer::class,
        ConfirmInvoicePaymentActionInterface::class => ConfirmInvoicePaymentAction::class,
        PremiumCreditsInterface::class => PremiumCreditRepository::class,
        PremiumAccessInterface::class => PremiumAccess::class,
        PremiumPackInvoiceInterface::class => PremiumPackInvoice::class,
        ConfirmPremiumPackActionInterface::class => ConfirmPremiumPackAction::class,
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    #[Override]
    public function register(): void
    {
        $this->app->when(AlbyClient::class)
            ->needs('$accessToken')
            ->giveConfig('services.alby.api_key');

        $this->app->when(PremiumAccess::class)
            ->needs('$npub')
            ->give(static fn (): string => nostr_pubkey() ?? '');

        $this->app->when(PremiumAccess::class)
            ->needs('$packSats')
            ->giveConfig('services.premium.pack_sats');

        $this->app->when(PremiumAccess::class)
            ->needs('$packMessages')
            ->giveConfig('services.premium.pack_messages');

        $this->app->when(AlbySettleWebhookAction::class)
            ->needs('$webhookSecret')
            ->giveConfig('services.alby.webhook_secret');

        foreach ([PremiumPackInvoice::class, AlbySettleWebhookAction::class, ConfirmPremiumPackAction::class] as $needsPackSize) {
            $this->app->when($needsPackSize)
                ->needs('$packMessages')
                ->giveConfig('services.premium.pack_messages');
        }

        $this->app->when(PremiumPackInvoice::class)
            ->needs('$packSats')
            ->giveConfig('services.premium.pack_sats');

        $this->app->when(PremiumPackInvoice::class)
            ->needs('$expirySeconds')
            ->giveConfig('services.rate_limit.invoice_expiry');
    }
}
