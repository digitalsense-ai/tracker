<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('simulated_trades', function (Blueprint $table) {
            $table->id();
            $table->string('ticker');
            $table->decimal('entry_price', 10, 2)->nullable();
            $table->decimal('exit_price', 10, 2)->nullable();
            $table->decimal('fees', 10, 2)->nullable();
            $table->decimal('net_profit', 10, 2)->nullable();
            $table->boolean('earnings_day')->default(false);
            $table->string('forecast_type')->nullable();
            $table->decimal('forecast_score', 5, 2)->nullable();
            $table->string('trend_rating')->nullable();
            $table->boolean('executed_on_nordnet')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('simulated_trades');
    }
};