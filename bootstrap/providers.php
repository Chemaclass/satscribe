<?php

use Modules\Blockchain\BlockchainServiceProvider;
use Modules\Chat\ChatServiceProvider;
use Modules\Faq\FaqServiceProvider;
use Modules\OpenAI\OpenAIServiceProvider;
use Modules\Payment\PaymentServiceProvider;
use Modules\Shared\RouteServiceProvider;
use Modules\Shared\SharedServiceProvider;
use Modules\UtxoTrace\UtxoTraceServiceProvider;

return [
    SharedServiceProvider::class,
    RouteServiceProvider::class,
    ChatServiceProvider::class,
    BlockchainServiceProvider::class,
    OpenAIServiceProvider::class,
    PaymentServiceProvider::class,
    UtxoTraceServiceProvider::class,
    FaqServiceProvider::class
];
