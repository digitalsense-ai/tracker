<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('trades', function (Blueprint $t) {
            if (!Schema::hasColumn('trades', 'market_session')) {
                $t->string('market_session', 32)->nullable()->after('forecast_type')->index();
            }
            if (!Schema::hasColumn('trades', 'closed_at')) {
                $t->timestamp('closed_at')->nullable()->after('updated_at');
            }
        });
    }
    public function down(): void {
        Schema::table('trades', function (Blueprint $t) {
            if (Schema::hasColumn('trades', 'market_session')) $t->dropColumn('market_session');
            if (Schema::hasColumn('trades', 'closed_at')) $t->dropColumn('closed_at');
        });
    }
};
