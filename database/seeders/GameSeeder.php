<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'name' => 'Blackjack',
                'min_bet' => 10.00,
                'max_bet' => 1000.00,
            ],
            [
                'name' => 'Roulette',
                'min_bet' => 5.00,
                'max_bet' => 500.00,
            ],
            [
                'name' => 'Poker',
                'min_bet' => 20.00,
                'max_bet' => 2000.00,
            ],
            [
                'name' => 'Slots',
                'min_bet' => 0.10,
                'max_bet' => 100.00,
            ],
            [
                'name' => 'Baccarat',
                'min_bet' => 15.00,
                'max_bet' => 1500.00,
            ],
            [
                'name' => 'Craps',
                'min_bet' => 5.00,
                'max_bet' => 750.00,
            ],
        ];

        foreach ($games as $game) {
            Game::factory()->create($game);
        }
    }
}
