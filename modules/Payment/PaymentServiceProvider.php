<?php

declare(strict_types=1);

namespace Modules\Payment;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\AlbyClient;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Application\CachedInvoiceValidator;
use Modules\Payment\Application\ConfirmInvoicePaymentAction;
use Modules\Payment\Application\PaywallInvoiceIssuer;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Modules\Payment\Domain\ConfirmInvoicePaymentActionInterface;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Repository\PaymentRepository;
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
    ];

    /** @var array<class-string, class-string> */
    public $bindings = [];

    #[Override]
    public function register(): void
    {
        $this->app->when(AlbyClient::class)
            ->needs('$accessToken')
            ->giveConfig('services.alby.api_key');

        $this->app->when(AlbySettleWebhookAction::class)
            ->needs('$webhookSecret')
            ->giveConfig('services.alby.webhook_secret');
    }
}
