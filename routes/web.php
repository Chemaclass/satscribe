<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\IpRateLimiter;
use Illuminate\Support\Facades\Route;

Route::redirect('generate', '/');
Route::redirect('describe', '/');

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/', [HomeController::class, 'createConversation'])
    ->name('home.create-conversation')
    ->middleware(IpRateLimiter::class);

Route::get('conversations/{conversation?}', [ConversationController::class, 'show'])->name('conversation.show');

Route::get('history', [HistoryController::class, 'index'])->name('history');
Route::get('history/{messageId}/raw', [HistoryController::class, 'getRaw'])->name('history.get-raw');

Route::get('faq', [FaqController::class, 'index'])->name('faq');
