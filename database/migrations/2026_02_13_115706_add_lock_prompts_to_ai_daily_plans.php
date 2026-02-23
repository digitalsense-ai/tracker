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
        Schema::table('ai_daily_plans', function (Blueprint $table) {
            $table->text('locked_start_prompt')->nullable()->after('plan_json');
            $table->text('locked_loop_prompt')->nullable()->after('locked_start_prompt');
            $table->text('locked_premarket_prompt')->nullable()->after('locked_loop_prompt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_daily_plans', function (Blueprint $table) {
            //
        });
    }
};
