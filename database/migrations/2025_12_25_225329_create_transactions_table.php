<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->enum('type', ['deposit', 'bet', 'win']);
            $table->decimal('amount', 18, 4);
            $table->decimal('balance_after', 18, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
