<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event_type')->index();
            $table->string('category')->default('system')->index();
            $table->string('severity')->default('info')->index();
            $table->string('source')->default('system')->index();
            $table->string('status')->default('recorded')->index();
            $table->uuid('correlation_id')->nullable()->index();
            $table->uuid('timeline_id')->nullable()->index();
            $table->nullableMorphs('subject');
            $table->json('metadata')->nullable();
            $table->json('explainability_refs')->nullable();
            $table->boolean('operator_visibility')->default(true)->index();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('timelines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('timeline_type')->default('operational')->index();
            $table->string('title')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->nullableMorphs('subject');
            $table->string('status')->default('open')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('consensus_decisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('correlation_id')->nullable()->index();
            $table->uuid('timeline_id')->nullable()->index();
            $table->nullableMorphs('subject');
            $table->string('decision_type')->default('model_consensus')->index();
            $table->string('recommendation')->nullable()->index();
            $table->unsignedTinyInteger('agreement_score')->default(0);
            $table->unsignedTinyInteger('conflict_score')->default(0);
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('conflict_level')->default('low')->index();
            $table->json('model_votes')->nullable();
            $table->json('weights')->nullable();
            $table->json('reason_codes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('observe_only')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('governance_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('correlation_id')->nullable()->index();
            $table->uuid('timeline_id')->nullable()->index();
            $table->nullableMorphs('subject');
            $table->string('event_type')->default('policy_evaluation')->index();
            $table->string('severity')->default('info')->index();
            $table->string('status')->default('recorded')->index();
            $table->string('recommended_action')->nullable()->index();
            $table->json('recommendations')->nullable();
            $table->json('reason_codes')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('operator_visibility')->default(true)->index();
            $table->boolean('observe_only')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('reason_artifacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('correlation_id')->nullable()->index();
            $table->uuid('timeline_id')->nullable()->index();
            $table->nullableMorphs('subject');
            $table->string('artifact_type')->default('reasoning')->index();
            $table->string('source')->default('system')->index();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->json('reason_codes')->nullable();
            $table->json('evidence')->nullable();
            $table->json('causal_links')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('operator_visibility')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reason_artifacts');
        Schema::dropIfExists('governance_events');
        Schema::dropIfExists('consensus_decisions');
        Schema::dropIfExists('timelines');
        Schema::dropIfExists('system_events');
    }
};
