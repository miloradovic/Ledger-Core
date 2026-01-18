<?php

declare(strict_types=1);

namespace App\Actions\Game;

class SimulateGameAction
{
    private const int WIN_PROBABILITY = 40;
    private const float WIN_MULTIPLIER = 1.5;

    /**
     * @return array<string, bool|float>
     */
    public function execute(float $betAmount): array
    {
        $isWin = random_int(1, 100) <= self::WIN_PROBABILITY;

        if ($isWin) {
            $winnings = $betAmount * self::WIN_MULTIPLIER;

            return [
                'win' => true,
                'amount' => $winnings,
            ];
        }

        return [
            'win' => false,
            'amount' => 0.00,
        ];
    }
}
