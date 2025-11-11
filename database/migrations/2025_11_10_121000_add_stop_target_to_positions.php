<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'stop_price')) {
                $table->decimal('stop_price', 18, 6)->nullable()->after('avg_price');
            }
            if (!Schema::hasColumn('positions', 'target_price')) {
                $table->decimal('target_price', 18, 6)->nullable()->after('stop_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'stop_price')) $table->dropColumn('stop_price');
            if (Schema::hasColumn('positions', 'target_price')) $table->dropColumn('target_price');
        });
    }
};
