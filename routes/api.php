<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Infrastructure\Http\Controller\AlbyWebhookController;
use Modules\Payment\Infrastructure\Http\Controller\InvoiceController;
use Modules\Payment\Infrastructure\Http\Controller\PremiumPackController;
use Modules\UtxoTrace\Infrastructure\Http\Controller\TraceUtxoController;

Route::post('/webhooks/alby', AlbyWebhookController::class)->name('api.webhooks.alby');
Route::get('/invoice/{identifier}/status', [InvoiceController::class, 'status'])->name('api.invoice.status');
Route::get('/trace-utxo/{utxo}', [TraceUtxoController::class, 'get'])->name('api.trace-utxo');

Route::get('/premium/balance', [PremiumPackController::class, 'balance'])->name('api.premium.balance');
Route::post('/premium/pack', [PremiumPackController::class, 'buy'])->name('api.premium.buy');
Route::get('/premium/pack/{paymentHash}/status', [PremiumPackController::class, 'status'])
    ->name('api.premium.status');
