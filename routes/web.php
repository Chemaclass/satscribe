<?php

use App\Http\Controllers\PromptResultController;
use App\Models\PromptResult;
use Illuminate\Support\Facades\Route;

Route::redirect('/','/describe');
Route::get('/describe', [PromptResultController::class, 'describe'])->name('describe');

Route::get('/history', fn() => view('history', [
    'descriptions' => PromptResult::latest()->paginate(10)
]));
