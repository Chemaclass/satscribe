<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/generate', '/');
Route::redirect('describe', '/');

Route::get('/', HomeController::class)->name('generate')
    ->middleware('throttle:generate');

Route::get('/history', HistoryController::class)->name('history');
Route::get('/faq', FaqController::class)->name('faq');
