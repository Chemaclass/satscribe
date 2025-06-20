<?php

use App\Http\Controllers\Api\AlbyWebhookController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\TraceUtxoController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/alby', AlbyWebhookController::class)->name('api.webhooks.alby');
Route::get('/invoice/{identifier}/status', [InvoiceController::class, 'status'])->name('api.invoice.status');
Route::get('/trace-utxo/{utxo}', [TraceUtxoController::class, 'get'])->name('api.trace-utxo');
