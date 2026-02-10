<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BetPlaced;
use Illuminate\Support\Facades\Log;

class LogBetActivity
{
    public function handle(BetPlaced $event): void
    {
        Log::channel('stack')->info('Bet placed', [
            'user_id' => $event->transaction->user_id,
            'amount' => $event->transaction->amount,
            'balance_before' => $event->balanceBefore,
            'balance_after' => $event->transaction->balance_after,
        ]);
    }
}
