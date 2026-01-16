<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['deposit', 'bet', 'win']),
            'amount' => $this->faker->randomFloat(4, 0, 1000),
            'balance_after' => $this->faker->randomFloat(4, 0, 10000),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function deposit(float $amount = 100.00): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $amount,
            ];
        });
    }

    public function bet(float $amount = 10.00): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'type' => 'bet',
                'amount' => -$amount,
                'balance_after' => $attributes['balance_after'] - $amount,
            ];
        });
    }

    public function win(float $amount = 50.00): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'type' => 'win',
                'amount' => $amount,
                'balance_after' => $attributes['balance_after'] + $amount,
            ];
        });
    }
}