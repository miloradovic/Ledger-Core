<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_bet',
        'max_bet'
    ];

    protected $casts = [
        'min_bet' => 'decimal:2',
        'max_bet' => 'decimal:2'
    ];
}
