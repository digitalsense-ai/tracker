<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('trades') && !Schema::hasColumn('trades','strategy_profile_id')) {
            Schema::table('trades', function (Blueprint $t) {
                $t->unsignedBigInteger('strategy_profile_id')->nullable()->after('id')->index();
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('trades') && Schema::hasColumn('trades','strategy_profile_id')) {
            Schema::table('trades', function (Blueprint $t) {
                $t->dropIndex(['strategy_profile_id']);
                $t->dropColumn('strategy_profile_id');
            });
        }
    }
};
