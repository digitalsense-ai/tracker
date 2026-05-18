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
        Schema::create('ai_decision_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module')->index();
            $table->string('activation_mode')->default('observe_only')->index();
            $table->nullableMorphs('subject');
            $table->string('recommended_action')->nullable()->index();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->unsignedTinyInteger('uncertainty')->nullable();
            $table->json('reason_codes')->nullable();
            $table->json('input_summary')->nullable();
            $table->json('output_payload')->nullable();
            $table->boolean('action_executed')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('ai_reality_checks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject');
            $table->boolean('plan_still_valid')->default(true);
            $table->boolean('setup_still_valid')->default(true);
            $table->boolean('regime_changed')->default(false);
            $table->boolean('news_risk_changed')->default(false);
            $table->string('recommended_action')->nullable()->index();
            $table->json('reason_codes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_confidence_snapshots', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedTinyInteger('uncertainty')->default(100);
            $table->string('level')->default('unknown')->index();
            $table->json('reason_codes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_regime_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->nullable()->index();
            $table->string('primary_regime')->default('unknown')->index();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->json('secondary_regimes')->nullable();
            $table->json('reason_codes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_regime_snapshots');
        Schema::dropIfExists('ai_confidence_snapshots');
        Schema::dropIfExists('ai_reality_checks');
        Schema::dropIfExists('ai_decision_logs');
    }
};
