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
        Schema::table('simulated_trades', function (Illuminate\Database\Schema\Blueprint $table) {
            if (!Schema::hasColumn('simulated_trades', 'status')) {
                $table->string('status')->nullable()->after('ticker');
            }
            if (!Schema::hasColumn('simulated_trades', 'entry_price')) {
                $table->decimal('entry_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'sl_price')) {
                $table->decimal('sl_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'tp1_price')) {
                $table->decimal('tp1_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'tp2_price')) {
                $table->decimal('tp2_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'tp3_price')) {
                $table->decimal('tp3_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'exit_price')) {
                $table->decimal('exit_price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'exit_type')) {
                $table->string('exit_type')->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'is_win')) {
                $table->boolean('is_win')->default(false);
            }
            if (!Schema::hasColumn('simulated_trades', 'pnl_amount')) {
                $table->decimal('pnl_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('simulated_trades', 'date')) {
                $table->date('date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
