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
        Schema::create('model_feedback_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->date('summary_date')->index();
            $table->unsignedInteger('trades_reviewed')->default(0);
            $table->decimal('win_rate', 8, 4)->nullable();
            $table->decimal('avg_r_multiple', 10, 4)->nullable();
            $table->string('top_failure_reason')->nullable();
            $table->json('failure_breakdown')->nullable();
            $table->text('recommended_changes')->nullable();
            $table->json('draft_prompt_changes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['ai_model_id', 'summary_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_feedback_summaries');
    }
};
