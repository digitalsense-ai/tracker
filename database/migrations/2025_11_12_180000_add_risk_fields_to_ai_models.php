<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models','goal_label')) $table->string('goal_label')->nullable()->after('name');
            if (!Schema::hasColumn('ai_models','start_equity')) $table->decimal('start_equity',14,2)->nullable()->after('equity');
            if (!Schema::hasColumn('ai_models','peak_equity')) $table->decimal('peak_equity',14,2)->nullable()->after('start_equity');

            if (!Schema::hasColumn('ai_models','max_concurrent_trades')) $table->unsignedInteger('max_concurrent_trades')->default(5)->after('risk_pct');
            if (!Schema::hasColumn('ai_models','allow_same_symbol_reentry')) $table->boolean('allow_same_symbol_reentry')->default(false)->after('max_concurrent_trades');
            if (!Schema::hasColumn('ai_models','cooldown_minutes')) $table->unsignedInteger('cooldown_minutes')->default(0)->after('allow_same_symbol_reentry');

            if (!Schema::hasColumn('ai_models','per_trade_alloc_pct')) $table->decimal('per_trade_alloc_pct',6,2)->default(20.00)->after('cooldown_minutes');
            if (!Schema::hasColumn('ai_models','max_exposure_pct')) $table->decimal('max_exposure_pct',6,2)->default(80.00)->after('per_trade_alloc_pct');
            if (!Schema::hasColumn('ai_models','max_leverage')) $table->decimal('max_leverage',6,2)->default(5.00)->after('max_exposure_pct');
            if (!Schema::hasColumn('ai_models','max_drawdown_pct')) $table->decimal('max_drawdown_pct',6,2)->default(0.00)->after('max_leverage');
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            foreach (['goal_label','start_equity','peak_equity','max_concurrent_trades','allow_same_symbol_reentry','cooldown_minutes','per_trade_alloc_pct','max_exposure_pct','max_leverage','max_drawdown_pct'] as $col) {
                if (Schema::hasColumn('ai_models',$col)) $table->dropColumn($col);
            }
        });
    }
};
