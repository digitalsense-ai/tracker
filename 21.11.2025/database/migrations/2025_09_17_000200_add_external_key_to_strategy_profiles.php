<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('strategy_profiles') && !Schema::hasColumn('strategy_profiles','external_key')) {
            Schema::table('strategy_profiles', function (Blueprint $t) {
                $t->string('external_key', 191)->nullable()->after('name')->index();
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('strategy_profiles') && Schema::hasColumn('strategy_profiles','external_key')) {
            Schema::table('strategy_profiles', function (Blueprint $t) {
                $t->dropIndex(['external_key']);
                $t->dropColumn('external_key');
            });
        }
    }
};