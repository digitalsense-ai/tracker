<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_model_id');
            $table->string('action')->nullable();
            $table->json('payload');
            $table->timestamps();
            $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_logs');
    }
};
