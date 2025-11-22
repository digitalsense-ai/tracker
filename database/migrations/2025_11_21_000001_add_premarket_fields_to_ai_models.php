<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models','premarket_prompt')) {
                $table->text('premarket_prompt')->nullable()->after('loop_prompt');
            }
            if (!Schema::hasColumn('ai_models','premarket_run_time')) {
                // Stored as HH:MM (24h) in app timezone
                $table->string('premarket_run_time', 8)->nullable()->after('premarket_prompt');
            }
            if (!Schema::hasColumn('ai_models','max_strategies_per_day')) {
                $table->unsignedInteger('max_strategies_per_day')->nullable()->after('premarket_run_time');
            }
            if (!Schema::hasColumn('ai_models','max_symbols_per_day')) {
                $table->unsignedInteger('max_symbols_per_day')->nullable()->after('max_strategies_per_day');
            }
            if (!Schema::hasColumn('ai_models','allow_sleeper_strategies')) {
                $table->boolean('allow_sleeper_strategies')->default(true)->after('max_symbols_per_day');
            }
            if (!Schema::hasColumn('ai_models','default_risk_per_strategy_pct')) {
                $table->decimal('default_risk_per_strategy_pct', 5, 2)->nullable()->after('allow_sleeper_strategies');
            }
            if (!Schema::hasColumn('ai_models','allow_activate_sleepers')) {
                $table->boolean('allow_activate_sleepers')->default(true)->after('default_risk_per_strategy_pct');
            }
            if (!Schema::hasColumn('ai_models','allow_early_exit_on_invalidation')) {
                $table->boolean('allow_early_exit_on_invalidation')->default(true)->after('allow_activate_sleepers');
            }
            if (!Schema::hasColumn('ai_models','max_adds_per_position')) {
                $table->unsignedTinyInteger('max_adds_per_position')->default(0)->after('allow_early_exit_on_invalidation');
            }
            if (!Schema::hasColumn('ai_models','loop_min_price_move_pct')) {
                $table->decimal('loop_min_price_move_pct', 6, 3)->nullable()->after('max_adds_per_position');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            foreach ([
                'premarket_prompt',
                'premarket_run_time',
                'max_strategies_per_day',
                'max_symbols_per_day',
                'allow_sleeper_strategies',
                'default_risk_per_strategy_pct',
                'allow_activate_sleepers',
                'allow_early_exit_on_invalidation',
                'max_adds_per_position',
                'loop_min_price_move_pct',
            ] as $col) {
                if (Schema::hasColumn('ai_models', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
