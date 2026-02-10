<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\Small;

#[Small]
class PlaceBetTest extends BaseTestCase
{
    use RefreshDatabase;

    public function testCanPlaceABetSuccessfully(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $response = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'bet',
            'amount' => -10.00,
        ]);
    }

    public function testPreventsBettingWithInsufficientBalance(): void
    {
        $user = User::factory()->create(['balance' => 5.00]);

        $response = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function testCanDepositMoneySuccessfully(): void
    {
        $user = User::factory()->create(['balance' => 50.00]);

        $response = $this->actingAs($user)
            ->postJson('/api/deposit', ['amount' => 50.00]);

        $response->assertSuccessful();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 50.00,
        ]);
    }

    public function testCanGetUserBalance(): void
    {
        $user = User::factory()->create(['balance' => 75.50]);

        $response = $this->actingAs($user)
            ->getJson('/api/balance');

        $response->assertSuccessful();
        $response->assertJson(['balance' => 75.50]);
    }

    public function testCanGetTransactionHistory(): void
    {
        $user = User::factory()->create(['balance' => 100.00]);

        // Create some transactions
        Transaction::factory()->create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => 50.00,
            'balance_after' => 150.00,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/transactions');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'transactions');
    }
}
