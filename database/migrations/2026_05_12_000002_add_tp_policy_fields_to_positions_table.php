<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('positions')) {
            return;
        }

        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'tp1_close_pct')) {
                $table->decimal('tp1_close_pct', 8, 4)->nullable()->after('tp_model');
            }

            if (!Schema::hasColumn('positions', 'move_sl_to_break_even_on_tp1')) {
                $table->boolean('move_sl_to_break_even_on_tp1')->nullable()->after('tp1_close_pct');
            }

            if (!Schema::hasColumn('positions', 'runner_trailing_enabled')) {
                $table->boolean('runner_trailing_enabled')->nullable()->after('move_sl_to_break_even_on_tp1');
            }

            if (!Schema::hasColumn('positions', 'runner_trail_distance_rr')) {
                $table->decimal('runner_trail_distance_rr', 8, 4)->nullable()->after('runner_trailing_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('positions')) {
            return;
        }

        Schema::table('positions', function (Blueprint $table) {
            foreach ([
                'runner_trail_distance_rr',
                'runner_trailing_enabled',
                'move_sl_to_break_even_on_tp1',
                'tp1_close_pct',
            ] as $column) {
                if (Schema::hasColumn('positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
