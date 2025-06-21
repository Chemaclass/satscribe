<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Infrastructure\Http\Controller\AlbyWebhookController;
use Modules\Payment\Infrastructure\Http\Controller\InvoiceController;
use Modules\UtxoTrace\Infrastructure\Http\Controller\TraceUtxoController;

Route::post('/webhooks/alby', AlbyWebhookController::class)->name('api.webhooks.alby');
Route::get('/invoice/{identifier}/status', [InvoiceController::class, 'status'])->name('api.invoice.status');
Route::get('/trace-utxo/{utxo}', [TraceUtxoController::class, 'get'])->name('api.trace-utxo');
