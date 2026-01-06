<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('positions', function (Blueprint $table) {
            if (!Schema::hasColumn('positions', 'plan_item_id')) {
                $table->uuid('plan_item_id')->nullable()->index();
            }
        });
    }
    public function down(): void {
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'plan_item_id')) {
                $table->dropColumn('plan_item_id');
            }
        });
    }
};
