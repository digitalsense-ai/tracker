<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_daily_candidate_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ai_model_id')->unsigned();
            $table->date('trade_date');
            $table->string('symbol', 20);
            $table->integer('rank');
            $table->double('score');
            $table->double('price');
            $table->integer('saxo_uic');
            $table->string('saxo_asset_type', 20);
            $table->json('metrics_json'); // ATR, momentum, range, etc.
            $table->string('source', 50); // 'scanner_v4'
            $table->timestamps();
            
            $table->unique(
                ['ai_model_id', 'trade_date', 'symbol'],
                'uniq_day_symbol'
            );

            $table->index(
                ['ai_model_id', 'trade_date', 'rank'],
                'idx_day_rank'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_daily_candidate_items');
    }
};
