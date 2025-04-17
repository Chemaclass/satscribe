<?php

use App\Http\Controllers\PromptResultController;
use Illuminate\Support\Facades\Route;

Route::redirect('/generate', '/');
Route::redirect('describe', '/');

Route::get('/history', [PromptResultController::class, 'history'])->name('history');
Route::get('/', [PromptResultController::class, 'generate'])->name('generate')
    ->middleware('throttle:generate');
