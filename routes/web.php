<?php

use App\Http\Controllers\PromptResultController;
use App\Models\PromptResult;
use Illuminate\Support\Facades\Route;

Route::get('/', [PromptResultController::class, 'index']);
Route::post('/describe', [PromptResultController::class, 'describe'])->name('describe');

Route::get('/history', function () {
    return view('history', [
        'descriptions' => PromptResult::latest()->paginate(10)
    ]);
});
