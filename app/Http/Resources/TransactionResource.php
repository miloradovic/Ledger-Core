<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'balance_after' => number_format((float) $this->balance_after, 2, '.', ''),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
