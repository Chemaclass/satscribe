<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\SatscribeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/generate', '/');
Route::redirect('describe', '/');

Route::get('/', [SatscribeController::class, 'index'])->name('home');
Route::get('/history', [HistoryController::class, 'index'])->name('history');
Route::get('/faq', [FaqController::class, 'index'])->name('faq');
