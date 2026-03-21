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
        Schema::create('trade_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->string('symbol')->index();
            $table->string('strategy')->nullable();
            $table->string('regime_at_entry')->nullable();
            $table->string('regime_at_exit')->nullable();
            $table->boolean('plan_aligned')->default(false);
            $table->boolean('should_have_opened')->nullable();
            $table->boolean('should_have_closed_earlier')->nullable();
            $table->unsignedTinyInteger('entry_quality_score')->nullable();
            $table->unsignedTinyInteger('exit_quality_score')->nullable();
            $table->string('failure_reason')->nullable()->index();
            $table->string('improvement_action')->nullable();
            $table->decimal('r_multiple', 10, 4)->nullable();
            $table->decimal('net_pnl', 14, 4)->nullable();
            $table->json('review_payload')->nullable();
            $table->string('review_source')->default('rule_engine');
            $table->timestamps();
            $table->unique('trade_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_reviews');
    }
};
