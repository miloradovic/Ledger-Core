<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:4',
            'balance_after' => 'decimal:4',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeBets(Builder $query): void
    {
        $query->where('type', TransactionType::Bet);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeWins(Builder $query): void
    {
        $query->where('type', TransactionType::Win);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    public function scopeRecent(Builder $query, int $limit = 50): void
    {
        $query->latest()->limit($limit);
    }
}
