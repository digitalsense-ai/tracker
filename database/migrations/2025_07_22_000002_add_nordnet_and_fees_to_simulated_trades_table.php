<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('simulated_trades', function (Blueprint $table) {
            $table->decimal('entry_price', 10, 2)->nullable();
            $table->decimal('exit_price', 10, 2)->nullable();
            $table->decimal('fees', 10, 2)->nullable()->comment('Calculated based on Nordnet fee model');
            $table->decimal('net_profit', 10, 2)->nullable();
            $table->boolean('earnings_day')->default(false);
            $table->string('forecast_type')->nullable();
            $table->string('forecast_score')->nullable();
            $table->string('trend_rating')->nullable();
            $table->boolean('executed_on_nordnet')->default(false)->comment('True if this trade was executed via Nordnet');
        });
    }

    public function down(): void {
        Schema::table('simulated_trades', function (Blueprint $table) {
            $table->dropColumn([
                'entry_price',
                'exit_price',
                'fees',
                'net_profit',
                'earnings_day',
                'forecast_type',
                'forecast_score',
                'trend_rating',
                'executed_on_nordnet'
            ]);
        });
    }
};
