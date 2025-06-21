<?php

use App\Providers\AppServiceProvider;
use App\Providers\RouteServiceProvider;
use Modules\Blockchain\BlockchainServiceProvider;
use Modules\Chat\ChatServiceProvider;
use Modules\OpenAI\OpenAIServiceProvider;
use Modules\Payment\PaymentServiceProvider;

return [
    AppServiceProvider::class,
    RouteServiceProvider::class,
    ChatServiceProvider::class,
    BlockchainServiceProvider::class,
    OpenAIServiceProvider::class,
    PaymentServiceProvider::class,
];
