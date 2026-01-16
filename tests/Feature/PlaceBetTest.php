<?php

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can place a bet successfully', function () {
    $user = User::factory()->create(['balance' => 100.00]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/spin', ['bet_amount' => 10.00]);

    $response->assertSuccessful();
    
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'bet',
        'amount' => -10.00
    ]);
});

it('prevents betting with insufficient balance', function () {
    $user = User::factory()->create(['balance' => 5.00]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/spin', ['bet_amount' => 10.00]);

    $response->assertStatus(400);
    $response->assertJson(['success' => false]);
});

it('can deposit money successfully', function () {
    $user = User::factory()->create(['balance' => 50.00]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/deposit', ['amount' => 50.00]);

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);
    
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'deposit',
        'amount' => 50.00
    ]);
});

it('can get user balance', function () {
    $user = User::factory()->create(['balance' => 75.50]);
    
    $response = $this->actingAs($user)
        ->getJson('/api/balance');

    $response->assertSuccessful();
    $response->assertJson(['balance' => 75.50]);
});

it('can get transaction history', function () {
    $user = User::factory()->create(['balance' => 100.00]);
    
    // Create some transactions
    Transaction::factory()->create([
        'user_id' => $user->id,
        'type' => 'deposit',
        'amount' => 50.00,
        'balance_after' => 150.00
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/transactions');

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'transactions');
});