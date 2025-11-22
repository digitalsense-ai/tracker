<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'cash')) {
                $table->decimal('cash', 14, 2)->nullable()->after('equity');
            }
            if (!Schema::hasColumn('ai_models', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('check_interval_min');
            }
            if (!Schema::hasColumn('ai_models', 'risk_pct')) {
                $table->decimal('risk_pct', 6, 3)->default(0.5)->after('last_checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (Schema::hasColumn('ai_models', 'cash')) $table->dropColumn('cash');
            if (Schema::hasColumn('ai_models', 'last_checked_at')) $table->dropColumn('last_checked_at');
            if (Schema::hasColumn('ai_models', 'risk_pct')) $table->dropColumn('risk_pct');
        });
    }
};
