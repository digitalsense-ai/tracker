<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_daily_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_model_id');
            $table->date('trade_date');
            $table->json('plan_json')->nullable();
            $table->timestamps();

            $table->unique(['ai_model_id', 'trade_date'], 'ai_daily_plans_unique_model_date');
            $table->foreign('ai_model_id')
                ->references('id')->on('ai_models')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_daily_plans');
    }
};
