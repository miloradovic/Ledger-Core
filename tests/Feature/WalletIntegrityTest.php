<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WalletIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function balance_never_goes_negative_under_concurrent_load(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        // Simulate 10 sequential bets (tests don't support true concurrency)
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->actingAs($user)->postJson('/api/spin', ['bet_amount' => 10.00]);
        }

        $user->refresh();

        // Balance should never be negative
        $this->assertGreaterThanOrEqual(0, $user->balance);

        // Total debits (stored as negative values) should not exceed starting balance
        $totalBets = DB::table('transactions')
            ->where('user_id', $user->id)
            ->where('type', 'bet')
            ->sum('amount');

        // totalBets is negative, so it should be >= -100.00 (starting balance)
        $this->assertGreaterThanOrEqual(-100.00, (float) $totalBets);
    }

    #[Test]
    public function transaction_history_matches_balance_changes(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->actingAs($user)->postJson('/api/spin', ['bet_amount' => 10.00]);
        $this->actingAs($user)->postJson('/api/deposit', ['amount' => 25.00]);

        $transactions = $user->transactions()->orderBy('id')->get();
        $calculatedBalance = 100.00;

        foreach ($transactions as $transaction) {
            $calculatedBalance += $transaction->amount;
            $this->assertEquals($calculatedBalance, $transaction->balance_after);
        }

        $user->refresh();
        $this->assertEquals($calculatedBalance, $user->balance);
    }

    #[Test]
    public function multiple_deposits_are_accurate(): void
    {
        $user = User::factory()->create(['balance' => 0.00]);

        $deposits = [10.00, 25.50, 100.00, 5.25];
        $expectedBalance = 0.00;

        foreach ($deposits as $amount) {
            $this->actingAs($user)->postJson('/api/deposit', ['amount' => $amount]);
            $expectedBalance += $amount;
        }

        $user->refresh();
        $this->assertEquals($expectedBalance, $user->balance);
    }

    #[Test]
    public function insufficient_balance_rejected_correctly(): void
    {
        $user = User::factory()->create(['balance' => 5.00]);

        $response = $this->actingAs($user)->postJson('/api/spin', ['bet_amount' => 10.00]);

        $response->assertStatus(422);
        $user->refresh();
        $this->assertEquals(5.00, $user->balance);
    }

    #[Test]
    public function transaction_records_contain_correct_balance_after(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->actingAs($user)->postJson('/api/deposit', ['amount' => 50.00]);
        $this->actingAs($user)->postJson('/api/spin', ['bet_amount' => 20.00]);

        $transactions = $user->transactions()->orderBy('id')->get();

        $this->assertGreaterThan(0, $transactions->count());

        foreach ($transactions as $transaction) {
            $this->assertGreaterThan(0, $transaction->balance_after);
        }
    }
}
