<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\IpRateLimiter;
use Illuminate\Support\Facades\Route;

Route::redirect('generate', '/');
Route::redirect('describe', '/');

Route::get('history', [HistoryController::class, 'index'])->name('history');
Route::get('history/{id}/raw', [HistoryController::class, 'getRaw'])->name('history.get-raw');

Route::get('faq', [FaqController::class, 'index'])->name('faq');

Route::get('/conversations/{conversation?}', [HomeController::class, 'index'])->name('home.conversations');

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/', [HomeController::class, 'submit'])->name('home.submit')->middleware(IpRateLimiter::class);
