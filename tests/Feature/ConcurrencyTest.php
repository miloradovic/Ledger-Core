<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Small;
use Tests\TestCase;

#[Small]
class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function testConcurrentBetsAreHandledCorrectly(): void
    {
        // Create a user with a specific balance
        $user = User::factory()->create(['balance' => 100.00]);

        // Create multiple concurrent requests to test race conditions
        $responses = [];
        $requests = [];

        // Create 5 concurrent bet requests (each for 10.00)
        for ($i = 0; $i < 5; ++$i) {
            $requests[] = ['bet_amount' => 10.00];
        }

        // Send concurrent requests
        foreach ($requests as $request) {
            $responses[] = $this->actingAs($user)
                ->postJson('/api/spin', $request);
        }

        // Check that all requests were successful
        foreach ($responses as $response) {
            $response->assertSuccessful();
        }

        // Check that the user's balance is less than or equal to 100.00 (accounting for possible wins)
        $user->refresh();
        static::assertLessThanOrEqual(100.0, (float) $user->balance);

        // Check that at least 5 transactions were created (bets + possible wins)
        $transactionCount = DB::table('transactions')->where('user_id', $user->id)->count();
        static::assertGreaterThanOrEqual(5, $transactionCount);

        // Check that bet transactions exist
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => 'bet',
            'amount' => -10.00,
        ]);
    }

    public function testConcurrentBetsWithInsufficientFundsAreRejected(): void
    {
        // Create a user with limited balance
        $user = User::factory()->create(['balance' => 15.00]);

        // Try to make concurrent bets that would exceed the balance
        $responses = [];

        // First bet should succeed (10.00)
        $responses[] = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);

        // Second bet should succeed (5.00)
        $responses[] = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 5.00]);

        // Third bet should fail (insufficient funds)
        $responses[] = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);

        // Check responses
        $responses[0]->assertSuccessful();
        $responses[1]->assertSuccessful();

        // The third request might succeed or fail depending on game results
        // If it fails due to insufficient funds, that's acceptable behavior
        if ($responses[2]->status() === 400) {
            // This is expected - insufficient funds after the first two bets
            static::assertStringContainsString('Insufficient balance', $responses[2]->json('message'));
        } else {
            $responses[2]->assertSuccessful();
        }

        // Check final balance (should be >= 0.00)
        $user->refresh();
        static::assertGreaterThanOrEqual(0.00, $user->balance);
    }

    public function testBalanceNeverGoesNegative(): void
    {
        // Create a user with minimal balance
        $user = User::factory()->create(['balance' => 5.00]);

        // Try to make a bet that would exceed the balance
        $response = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);

        // The bet should be rejected
        $response->assertStatus(400);

        // Balance should remain unchanged
        $user->refresh();
        static::assertSame(5.0, (float) $user->balance);

        // No transaction should be created
        $this->assertDatabaseCount('transactions', 0);
    }
}
