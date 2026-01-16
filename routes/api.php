<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/spin', [GameController::class, 'spin']);
    Route::post('/deposit', [GameController::class, 'deposit']);
    Route::get('/balance', [GameController::class, 'balance']);
    Route::get('/transactions', [GameController::class, 'transactions']);
});