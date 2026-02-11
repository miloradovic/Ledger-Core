<?php

declare(strict_types=1);

namespace App\Actions\Wallet;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepositAction
{
    /**
     * @param  numeric-string  $amount
     * @return array<string, bool|string>
     */
    public function execute(User $user, string $amount): array
    {
        return DB::transaction(static function () use ($user, $amount): array {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $newBalance = bcadd((string) $user->balance, $amount, 4);
            $user->balance = $newBalance;
            $user->save();

            Transaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Deposit,
                'amount' => $amount,
                'balance_after' => $newBalance,
            ]);

            return [
                'success' => true,
                'new_balance' => $newBalance,
            ];
        });
    }
}
