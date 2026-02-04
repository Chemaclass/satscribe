<?php

use Illuminate\Support\Facades\Route;
use Modules\Blockchain\Infrastructure\Http\Controller\PrefetchController;
use Modules\Chat\Infrastructure\Http\Controller\ChatController;
use Modules\Chat\Infrastructure\Http\Controller\HistoryController;
use Modules\Faq\Infrastructure\Http\Controller\FaqController;
use Modules\Nostr\Infrastructure\Http\Controller\NostrAuthController;
use Modules\Nostr\Infrastructure\Http\Controller\NostrPageController;
use Modules\Nostr\Infrastructure\Http\Controller\ProfileController;
use Modules\Shared\Infrastructure\Http\Middleware\IpRateLimiter;
use Modules\UtxoTrace\Infrastructure\Http\Controller\TraceUtxoPageController;

Route::get('sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0', 'changefreq' => 'daily'],
        ['loc' => url('/faq'), 'priority' => '0.8', 'changefreq' => 'weekly'],
        ['loc' => url('/history'), 'priority' => '0.7', 'changefreq' => 'daily'],
        ['loc' => url('/trace-utxo'), 'priority' => '0.7', 'changefreq' => 'weekly'],
        ['loc' => url('/nostr'), 'priority' => '0.6', 'changefreq' => 'monthly'],
    ];

    return response()
        ->view('sitemap', ['urls' => $urls])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::redirect('generate', '/');
Route::redirect('describe', '/');

Route::get('/', [ChatController::class, 'index'])->name('home.index');
Route::post('/', [ChatController::class, 'createChat'])
    ->name('home.create-chat')
    ->middleware(IpRateLimiter::class);
Route::post('stream', [ChatController::class, 'createChatStream'])
    ->name('home.create-chat-stream')
    ->middleware(IpRateLimiter::class);

Route::get('chats/{chat?}', [ChatController::class, 'show'])->name('chat.show');
Route::post('chats/{chat}/messages', [ChatController::class, 'addMessage'])
    ->name('chat.add-message')
    ->middleware(IpRateLimiter::class);
Route::post('chats/{chat}/messages/stream', [ChatController::class, 'addMessageStream'])
    ->name('chat.add-message-stream')
    ->middleware(IpRateLimiter::class);
Route::post('chats/{chat}/share', [ChatController::class, 'share'])->name('chat.share');
Route::post('chats/{chat}/visibility', [ChatController::class, 'toggleVisibility'])->name('chat.toggle-visibility');

Route::get('history', [HistoryController::class, 'index'])->name('history.index');
Route::get('api/prefetch', [PrefetchController::class, 'prefetch'])->name('api.prefetch');
Route::get('history/{messageId}/raw', [HistoryController::class, 'getRaw'])->name('history.get-raw');

Route::get('faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('trace-utxo', [TraceUtxoPageController::class,'index'])->name('trace-utxo.page');
Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::get('nostr', [NostrPageController::class, 'index'])->name('nostr.index');

Route::get('auth/nostr/challenge', [NostrAuthController::class, 'challenge'])->name('nostr.challenge');
Route::post('auth/nostr/login', [NostrAuthController::class, 'login'])->name('nostr.login');
Route::post('auth/nostr/logout', [NostrAuthController::class, 'logout'])->name('nostr.logout');
