<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    public function __construct(
        private readonly Request $request,
    ) {
    }

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        Log::channel('transactions')->info('Transaction created', [
            'transaction_id' => $transaction->id,
            'type' => $transaction->type,
            'amount' => $transaction->amount,
            'balance_after' => $transaction->balance_after,
            'user_id' => $transaction->user_id,
            'ip' => $this->request->ip(),
            'session_id' => $this->request->hasSession() ? $this->request->session()->getId() : null,
            'timestamp' => $transaction->created_at?->toIso8601String(),
        ]);
    }
}
