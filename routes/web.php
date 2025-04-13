<?php

use App\Http\Controllers\DescribeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DescribeController::class, 'index']);
Route::post('/describe', [DescribeController::class, 'describe'])->name('describe');
