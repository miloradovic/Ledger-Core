<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'balance' => 5000.00,
        ]);

        // Create admin user
        User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Admin User',
            'balance' => 10000.00,
        ]);

        // Create additional demo users
        $users = User::factory()
            ->count(10)
            ->create();

        // Generate transactions for each user
        foreach ($users as $user) {
            $balance = fake()->randomFloat(2, 100, 5000);
            $user->update(['balance' => $balance]);

            // Initial deposit
            Transaction::factory()
                ->for($user)
                ->deposit($balance)
                ->create();

            // Generate random transactions
            $transactionCount = fake()->numberBetween(1, 20);
            for ($i = 0; $i < $transactionCount; $i++) {
                $transactionType = fake()->randomElement(['bet', 'win']);
                $currentBalance = $user->balance;

                if ($transactionType === 'bet') {
                    $amount = fake()->randomFloat(2, 5, min(100, $currentBalance * 0.5));
                    Transaction::factory()
                        ->for($user)
                        ->bet($amount)
                        ->create([
                            'balance_after' => max(0, $currentBalance - $amount),
                        ]);
                    $user->update(['balance' => max(0, $currentBalance - $amount)]);
                } else {
                    $amount = fake()->randomFloat(2, 10, 500);
                    Transaction::factory()
                        ->for($user)
                        ->win($amount)
                        ->create([
                            'balance_after' => $currentBalance + $amount,
                        ]);
                    $user->update(['balance' => $currentBalance + $amount]);
                }
            }
        }
    }
}
