<?php

use App\Http\Controllers\PromptResultController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/generate');
Route::redirect('describe', '/generate');
Route::get('/history', [PromptResultController::class, 'history'])->name('history');
Route::get('/generate', [PromptResultController::class, 'generate'])->name('generate')
    ->middleware('throttle:generate');
