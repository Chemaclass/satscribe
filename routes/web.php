<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Infrastructure\Http\Controller\ChatController;
use Modules\Chat\Infrastructure\Http\Controller\HistoryController;
use Modules\Faq\Infrastructure\Http\Controller\FaqController;
use Modules\Shared\Infrastructure\Http\Middleware\IpRateLimiter;
use Modules\UtxoTrace\Infrastructure\Http\Controller\TraceUtxoPageController;
use Modules\NostrAuth\Infrastructure\Http\Controller\NostrAuthController;

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

Route::get('auth/nostr/challenge', [NostrAuthController::class, 'challenge'])->name('nostr.challenge');
Route::post('auth/nostr/login', [NostrAuthController::class, 'login'])->name('nostr.login');
Route::post('auth/nostr/logout', [NostrAuthController::class, 'logout'])->name('nostr.logout');
