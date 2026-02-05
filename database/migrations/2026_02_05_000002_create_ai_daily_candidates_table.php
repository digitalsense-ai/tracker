<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('ai_daily_candidates')) {
            return;
        }

        Schema::create('ai_daily_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_model_id');
            $table->date('trade_date');
            $table->json('symbols_json'); // ["AAPL","MSFT",...]
            $table->json('meta_json')->nullable(); // optional debug info
            $table->timestamps();

            $table->unique(['ai_model_id', 'trade_date']);
            $table->index(['trade_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_daily_candidates');
    }
};
