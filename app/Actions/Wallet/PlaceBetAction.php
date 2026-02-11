<?php

declare(strict_types=1);

namespace App\Actions\Wallet;

use App\Actions\Game\SimulateGameAction;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceBetAction
{
    public function __construct(
        private readonly SimulateGameAction $simulateGameAction,
        private readonly Request $request,
    ) {}

    /**
     * @param  numeric-string  $betAmount
     * @return array<string, bool|string>
     */
    public function execute(User $user, string $betAmount): array
    {
        return DB::transaction(function () use ($user, $betAmount): array {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if (bccomp((string) $user->balance, $betAmount, 4) < 0) {
                Log::channel('transactions')->warning('Insufficient balance for bet', [
                    'event' => 'bet_rejected',
                    'user_id' => $user->id,
                    'requested_amount' => $betAmount,
                    'available_balance' => $user->balance,
                    'ip' => $this->request->ip(),
                    'session_id' => $this->request->hasSession() ? $this->request->session()->getId() : null,
                ]);

                throw new InsufficientBalanceException(
                    required: $betAmount,
                    available: (string) $user->balance
                );
            }

            $newBalance = bcsub((string) $user->balance, $betAmount, 4);
            $user->balance = $newBalance;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Bet,
                'amount' => bcsub('0', $betAmount, 4),
                'balance_after' => $newBalance,
            ]);

            $gameResult = $this->simulateGameAction->execute($betAmount);

            if ($gameResult['win']) {
                $winnings = $gameResult['amount'];

                $winBalance = bcadd($newBalance, $winnings, 4);
                $user->balance = $winBalance;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => TransactionType::Win,
                    'amount' => $winnings,
                    'balance_after' => $winBalance,
                ]);

                return [
                    'win' => true,
                    'winnings' => $winnings,
                    'new_balance' => $winBalance,
                ];
            }

            return [
                'win' => false,
                'winnings' => '0.0000',
                'new_balance' => $newBalance,
            ];
        });
    }
}
