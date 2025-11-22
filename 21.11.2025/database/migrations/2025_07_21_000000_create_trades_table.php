<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('ticker');
            $table->date('date');
            $table->decimal('entry_price', 10, 2);
            $table->decimal('exit_price', 10, 2);
            $table->decimal('stop_loss', 10, 2);
            $table->enum('result', ['win', 'loss', 'breakeven']);
            $table->string('forecast_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('trades');
    }
};
