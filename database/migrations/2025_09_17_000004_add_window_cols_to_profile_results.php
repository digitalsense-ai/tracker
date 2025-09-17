<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('profile_results')) {
            Schema::table('profile_results', function (Blueprint $t) {
                if (!Schema::hasColumn('profile_results','window_start')) {
                    $t->dateTime('window_start')->nullable()->after('strategy_profile_id');
                }
                if (!Schema::hasColumn('profile_results','window_end')) {
                    $t->dateTime('window_end')->nullable()->after('window_start');
                }
            });
        }
    }
    public function down(): void {
        // keep columns on rollback to avoid losing history
    }
};