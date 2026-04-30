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
            $table->unsignedTinyInteger('min_entry_score')->default(8)->after('active')->comment('Use 1–10 scale:
1-4 = bad / no trade
5-6 = weak
7 = acceptable
8 = good
9 = very strong
10 = exceptional');
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
