<?php

declare(strict_types=1);

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', static fn () => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'laravelVersion' => Application::VERSION,
    'phpVersion' => \PHP_VERSION,
]));

Route::get('/dashboard', static fn () => Inertia::render('Dashboard'))->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(static function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Health check endpoints for Docker/monitoring
Route::get('/health', [HealthController::class, 'ping']);
Route::get('/health/check', [HealthController::class, 'check']);

require __DIR__.'/auth.php';
