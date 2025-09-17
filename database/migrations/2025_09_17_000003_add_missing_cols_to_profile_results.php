<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('profile_results')) {
            Schema::table('profile_results', function (Blueprint $t) {
                if (!Schema::hasColumn('profile_results','trades')) {
                    $t->integer('trades')->default(0)->after('window_end');
                }
                if (!Schema::hasColumn('profile_results','pnl')) {
                    $t->decimal('pnl', 18, 4)->default(0)->after('trades');
                }
                if (!Schema::hasColumn('profile_results','win_rate')) {
                    $t->decimal('win_rate', 5, 2)->default(0)->after('pnl');
                }
            });
        }
    }
    public function down(): void {
        // Keep columns on rollback to avoid data loss
    }
};