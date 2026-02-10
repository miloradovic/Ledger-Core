<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'win' => $data['win'],
            'winnings' => $data['win']
                ? number_format((float) $data['winnings'], 2, '.', '')
                : '0.00',
            'new_balance' => number_format((float) $data['new_balance'], 2, '.', ''),
        ];
    }
}
