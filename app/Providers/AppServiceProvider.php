<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\AddMessageAction;
use App\Actions\AlbySettleWebhookAction;
use App\Actions\CreateChatAction;
use App\Http\Middleware\IpRateLimiter;
use App\Repositories\ChatRepository;
use App\Repositories\ChatRepositoryInterface;
use App\Repositories\FaqRepository;
use App\Repositories\FaqRepositoryInterface;
use App\Repositories\FlaggedWordRepository;
use App\Repositories\FlaggedWordRepositoryInterface;
use App\Repositories\MessageRepository;
use App\Repositories\MessageRepositoryInterface;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentRepositoryInterface;
use App\Services\Alby\AlbyClient;
use App\Services\Alby\AlbyClientInterface;
use App\Services\BlockHeightProvider;
use App\Services\CachedInvoiceValidator;
use App\Services\CachedInvoiceValidatorInterface;
use App\Services\OpenAIService;
use App\Services\PriceService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        AlbyClientInterface::class => AlbyClient::class,
        ChatRepositoryInterface::class => ChatRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
        FaqRepositoryInterface::class => FaqRepository::class,
        FlaggedWordRepositoryInterface::class => FlaggedWordRepository::class,
        PaymentRepositoryInterface::class => PaymentRepository::class,
        CachedInvoiceValidatorInterface::class => CachedInvoiceValidator::class,
    ];

    public function register(): void
    {
        $this->app->bind(CarbonInterface::class, fn() => Carbon::now());

        $this->registerBindingsForCreateChatAction();
        $this->registerBindingsForAddMessageAction();
        $this->registerBindingsForOpenAIService();
        $this->registerBindingsForPriceService();
        $this->registerBindingsForBlockHeightProvider();
        $this->registerBindingsForIpRateLimiter();
        $this->registerBindingsForChatRepository();
        $this->registerBindingsForAlbyClient();
    }

    public function boot(): void
    {
        if (app()->environment('prod')) {
            URL::forceScheme('https');
        }

        Paginator::useTailwind();

        View::share('cronitorClientKey', config('app.cronitorClientKey'));

        $priceService = app(PriceService::class);
        View::share('btcPriceUsd', $priceService->getCurrentBtcPriceUsd());
        View::share('btcPriceEur', $priceService->getCurrentBtcPriceEur());
        View::share('btcPriceCny', $priceService->getCurrentBtcPriceCny());
        View::share('btcPriceGbp', $priceService->getCurrentBtcPriceGbp());
    }

    private function registerBindingsForCreateChatAction(): void
    {
        $this->app->when(CreateChatAction::class)
            ->needs('$trackingId')
            ->give(tracking_id());

        $this->app->when(CreateChatAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');
    }

    private function registerBindingsForAddMessageAction(): void
    {
        $this->app->when(AddMessageAction::class)
            ->needs('$trackingId')
            ->give(tracking_id());

        $this->app->when(AddMessageAction::class)
            ->needs('$maxOpenAIAttempts')
            ->giveConfig('services.openai.max_attempts');
    }

    private function registerBindingsForOpenAIService(): void
    {
        $this->app->when(OpenAIService::class)
            ->needs('$openAiApiKey')
            ->giveConfig('services.openai.key');

        $this->app->when(OpenAIService::class)
            ->needs('$openAiModel')
            ->giveConfig('services.openai.model');
    }

    private function registerBindingsForPriceService(): void
    {
        $this->app->when(PriceService::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_price');
    }

    private function registerBindingsForBlockHeightProvider(): void
    {
        $this->app->when(BlockHeightProvider::class)
            ->needs('$enabled')
            ->giveConfig('features.btc_block_height');
    }

    private function registerBindingsForIpRateLimiter(): void
    {
        $this->app->when(IpRateLimiter::class)
            ->needs('$maxAttempts')
            ->giveConfig('services.rate_limit.max_attempts');

        $this->app->when(IpRateLimiter::class)
            ->needs('$lnInvoiceAmountInSats')
            ->giveConfig('services.rate_limit.invoice_amount');

        $this->app->when(IpRateLimiter::class)
            ->needs('$lnInvoiceExpirySeconds')
            ->giveConfig('services.rate_limit.invoice_expiry');
    }

    private function registerBindingsForChatRepository(): void
    {
        $this->app->when(ChatRepository::class)
            ->needs('$perPage')
            ->giveConfig('app.pagination.per_page');

        $this->app->when(ChatRepository::class)
            ->needs('$trackingId')
            ->give(tracking_id());
    }

    private function registerBindingsForAlbyClient(): void
    {
        $this->app->when(AlbyClient::class)
            ->needs('$accessToken')
            ->giveConfig('services.alby.api_key');

        $this->app->when(AlbySettleWebhookAction::class)
            ->needs('$webhookSecret')
            ->giveConfig('services.alby.webhook_secret');
    }
}
