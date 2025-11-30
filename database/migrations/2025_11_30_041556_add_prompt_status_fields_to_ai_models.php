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
        Schema::table('ai_models', function (Blueprint $table) {
            $table->boolean('start_prompt_status')->default(true)->after('start_prompt');
            $table->boolean('loop_prompt_status')->default(true)->after('loop_prompt');
            $table->boolean('premarket_prompt_status')->default(true)->after('premarket_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            //
        });
    }
};
