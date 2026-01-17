<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(static function (): void {
    Route::post('/spin', [GameController::class, 'spin']);
    Route::post('/deposit', [GameController::class, 'deposit']);
    Route::get('/balance', [GameController::class, 'balance']);
    Route::get('/transactions', [GameController::class, 'transactions']);
});
