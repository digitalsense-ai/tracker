<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('simulated_trades', function (Blueprint $table) {
            if (!Schema::hasColumn('simulated_trades', 'date')) {
                $table->date('date')->nullable()->after('ticker');
            }
            if (!Schema::hasColumn('simulated_trades', 'sl_price')) {
                $table->decimal('sl_price', 10, 2)->nullable()->after('exit_price');
            }
            if (!Schema::hasColumn('simulated_trades', 'tp1')) {
                $table->decimal('tp1', 10, 2)->nullable()->after('sl_price');
            }
            if (!Schema::hasColumn('simulated_trades', 'tp2')) {
                $table->decimal('tp2', 10, 2)->nullable()->after('tp1');
            }
            if (!Schema::hasColumn('simulated_trades', 'status')) {
                $table->string('status')->nullable()->after('tp2');
            }
        });
    }

    public function down(): void {
        Schema::table('simulated_trades', function (Blueprint $table) {
            if (Schema::hasColumn('simulated_trades', 'status')) $table->dropColumn('status');
            if (Schema::hasColumn('simulated_trades', 'tp2')) $table->dropColumn('tp2');
            if (Schema::hasColumn('simulated_trades', 'tp1')) $table->dropColumn('tp1');
            if (Schema::hasColumn('simulated_trades', 'sl_price')) $table->dropColumn('sl_price');
            if (Schema::hasColumn('simulated_trades', 'date')) $table->dropColumn('date');
        });
    }
};
