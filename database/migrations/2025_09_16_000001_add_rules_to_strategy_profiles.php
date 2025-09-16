<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('strategy_profiles', function (Blueprint $t) {
            if (!Schema::hasColumn('strategy_profiles','rules')) {
                $t->json('rules')->nullable()->after('name');
            }
            if (!Schema::hasColumn('strategy_profiles','enabled')) {
                $t->boolean('enabled')->default(true)->after('rules')->index();
            }
        });
    }
    public function down(): void {
        Schema::table('strategy_profiles', function (Blueprint $t) {
            if (Schema::hasColumn('strategy_profiles','rules'))   $t->dropColumn('rules');
            if (Schema::hasColumn('strategy_profiles','enabled')) $t->dropColumn('enabled');
        });
    }
};