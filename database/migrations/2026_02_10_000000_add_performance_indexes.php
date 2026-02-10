<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', static function (Blueprint $table): void {
            // Index for transaction history queries
            $table->index(['user_id', 'created_at']);

            // Index for type-specific queries
            $table->index(['user_id', 'type', 'created_at']);

            // Index for balance calculations
            $table->index(['user_id', 'balance_after']);
        });

        Schema::table('users', static function (Blueprint $table): void {
            // Index for balance lookups
            $table->index('balance');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', static function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['user_id', 'type', 'created_at']);
            $table->dropIndex(['user_id', 'balance_after']);
        });

        Schema::table('users', static function (Blueprint $table): void {
            $table->dropIndex(['balance']);
        });
    }
};
