<?php

declare(strict_types=1);

namespace App\Actions\Game;

class SimulateGameAction
{
    private const int WIN_PROBABILITY = 40;

    private const string WIN_MULTIPLIER = '1.5';

    /**
     * @param  numeric-string  $betAmount
     * @return array{win: bool, amount: numeric-string}
     */
    public function execute(string $betAmount): array
    {
        $isWin = random_int(1, 100) <= self::WIN_PROBABILITY;

        if ($isWin) {
            $winnings = bcmul($betAmount, self::WIN_MULTIPLIER, 4);

            return [
                'win' => true,
                'amount' => $winnings,
            ];
        }

        return [
            'win' => false,
            'amount' => '0.0000',
        ];
    }
}
