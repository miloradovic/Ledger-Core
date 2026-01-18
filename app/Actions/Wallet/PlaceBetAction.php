<?php

declare(strict_types=1);

namespace App\Actions\Wallet;

use App\Actions\Game\SimulateGameAction;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PlaceBetAction
{
    public function __construct(
        private SimulateGameAction $simulateGameAction
    ) {
    }

    /**
     * @return array<string, bool|float>
     */
    public function execute(User $user, float $betAmount): array
    {
        return DB::transaction(function () use ($user, $betAmount): array {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            if ($user->balance < $betAmount) {
                throw new Exception('Insufficient balance');
            }

            $user->balance -= $betAmount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'bet',
                'amount' => -$betAmount,
                'balance_after' => $user->balance,
            ]);

            $gameResult = $this->simulateGameAction->execute($betAmount);

            if ($gameResult['win']) {
                $winnings = $gameResult['amount'];

                $user->balance += $winnings;
                $user->save();

                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'win',
                    'amount' => $winnings,
                    'balance_after' => $user->balance,
                ]);

                return [
                    'win' => true,
                    'winnings' => $winnings,
                    'new_balance' => $user->balance,
                ];
            }

            return [
                'win' => false,
                'winnings' => 0.00,
                'new_balance' => $user->balance,
            ];
        });
    }
}
