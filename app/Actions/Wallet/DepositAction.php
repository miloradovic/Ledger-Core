<?php

declare(strict_types=1);

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class DepositAction
{
    /**
     * @return array<string, bool|float|int>
     */
    public function execute(User $user, float $amount): array
    {
        return DB::transaction(static function () use ($user, $amount): array {
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $user->balance += $amount;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $user->balance,
            ]);

            return [
                'success' => true,
                'new_balance' => $user->balance,
            ];
        });
    }
}
