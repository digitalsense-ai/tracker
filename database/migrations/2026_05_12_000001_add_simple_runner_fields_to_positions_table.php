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
            if (!Schema::hasColumn('positions', 'remaining_qty')) {
                $table->decimal('remaining_qty', 18, 6)->nullable()->after('qty');
            }

            if (!Schema::hasColumn('positions', 'initial_stop_price')) {
                $table->decimal('initial_stop_price', 18, 6)->nullable()->after('stop_price');
            }

            if (!Schema::hasColumn('positions', 'tp1_hit')) {
                $table->boolean('tp1_hit')->default(false)->after('target_price');
            }

            if (!Schema::hasColumn('positions', 'runner_active')) {
                $table->boolean('runner_active')->default(false)->after('tp1_hit');
            }

            if (!Schema::hasColumn('positions', 'highest_price')) {
                $table->decimal('highest_price', 18, 6)->nullable()->after('runner_active');
            }

            if (!Schema::hasColumn('positions', 'tp_model')) {
                $table->string('tp_model')->nullable()->after('highest_price');
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
                'tp_model',
                'highest_price',
                'runner_active',
                'tp1_hit',
                'initial_stop_price',
                'remaining_qty',
            ] as $column) {
                if (Schema::hasColumn('positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
