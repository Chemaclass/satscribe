<?php

use App\Http\Controllers\Api\AlbyWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/alby', AlbyWebhookController::class)->name('webhooks.alby');
