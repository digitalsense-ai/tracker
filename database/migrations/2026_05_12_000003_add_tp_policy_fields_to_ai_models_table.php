<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'take_profit_enabled')) {
                $table->boolean('take_profit_enabled')->nullable()->after('loop_min_price_move_pct');
            }

            if (!Schema::hasColumn('ai_models', 'tp_model')) {
                $table->string('tp_model')->nullable()->after('take_profit_enabled');
            }

            if (!Schema::hasColumn('ai_models', 'tp1_close_pct')) {
                $table->decimal('tp1_close_pct', 8, 4)->nullable()->after('tp_model');
            }

            if (!Schema::hasColumn('ai_models', 'move_sl_to_break_even_on_tp1')) {
                $table->boolean('move_sl_to_break_even_on_tp1')->nullable()->after('tp1_close_pct');
            }

            if (!Schema::hasColumn('ai_models', 'runner_trailing_enabled')) {
                $table->boolean('runner_trailing_enabled')->nullable()->after('move_sl_to_break_even_on_tp1');
            }

            if (!Schema::hasColumn('ai_models', 'runner_trail_distance_rr')) {
                $table->decimal('runner_trail_distance_rr', 8, 4)->nullable()->after('runner_trailing_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        Schema::table('ai_models', function (Blueprint $table) {
            foreach ([
                'runner_trail_distance_rr',
                'runner_trailing_enabled',
                'move_sl_to_break_even_on_tp1',
                'tp1_close_pct',
                'tp_model',
                'take_profit_enabled',
            ] as $column) {
                if (Schema::hasColumn('ai_models', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
