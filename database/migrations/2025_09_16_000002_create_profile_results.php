<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('profile_results')) {
            Schema::create('profile_results', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('profile_id')->index();
                $t->dateTime('window_start')->index();
                $t->dateTime('window_end')->index();
                $t->integer('trades')->default(0);
                $t->decimal('pnl', 18, 4)->default(0);
                $t->decimal('win_rate', 5, 2)->default(0);
                $t->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('profile_results');
    }
};