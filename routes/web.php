<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\TraceUtxoPageController;
use App\Http\Middleware\IpRateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Chat\Infrastructure\Http\Controller\ChatController;

Route::redirect('generate', '/');
Route::redirect('describe', '/');

Route::get('/', [ChatController::class, 'index'])->name('home.index');
Route::post('/', [ChatController::class, 'createChat'])
    ->name('home.create-chat')
    ->middleware(IpRateLimiter::class);

Route::get('chats/{chat?}', [ChatController::class, 'show'])->name('chat.show');
Route::post('chats/{chat}/messages', [ChatController::class, 'addMessage'])
    ->name('chat.add-message')
    ->middleware(IpRateLimiter::class);

Route::get('history', [HistoryController::class, 'index'])->name('history.index');
Route::get('history/{messageId}/raw', [HistoryController::class, 'getRaw'])->name('history.get-raw');

Route::get('faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('trace-utxo', [TraceUtxoPageController::class,'index'])->name('trace-utxo.page');
