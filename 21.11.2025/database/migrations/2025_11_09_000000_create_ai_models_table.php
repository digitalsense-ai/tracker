<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('wallet')->nullable();
            $table->decimal('equity', 14, 2)->nullable();
            $table->decimal('return_pct', 7, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->text('start_prompt')->nullable();
            $table->text('loop_prompt')->nullable();
            $table->unsignedInteger('check_interval_min')->default(15);
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
