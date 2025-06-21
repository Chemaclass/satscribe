<?php
declare(strict_types=1);

namespace Modules\Payment;

use Illuminate\Support\ServiceProvider;
use Modules\Payment\Application\AlbyClient;
use Modules\Payment\Application\AlbySettleWebhookAction;
use Modules\Payment\Application\CachedInvoiceValidator;
use Modules\Payment\Domain\AlbyClientInterface;
use Modules\Payment\Domain\CachedInvoiceValidatorInterface;
use Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use Modules\Payment\Infrastructure\Repository\PaymentRepository;
use Override;

final class PaymentServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public $singletons = [
        AlbyClientInterface::class => AlbyClient::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        CachedInvoiceValidatorInterface::class => CachedInvoiceValidator::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    public $bindings = [];

    /**
     * Register any application services.
     */
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
