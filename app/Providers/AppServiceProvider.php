<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\BetPlaced;
use App\Listeners\LogBetActivity;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Transaction::observe(TransactionObserver::class);

        Event::listen(
            BetPlaced::class,
            LogBetActivity::class,
        );
    }
}
