<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('profile_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_profile_id')->constrained()->cascadeOnDelete();
            $table->string('window')->nullable();
            $table->integer('trades')->default(0);
            $table->float('winrate')->default(0);
            $table->float('avg_r')->default(0);
            $table->float('net_pl')->default(0);
            $table->float('profit_factor')->default(0);
            $table->float('drawdown_pct')->default(0);
            $table->float('score')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('profile_results');
    }
};