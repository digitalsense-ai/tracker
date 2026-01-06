<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('trades', function (Blueprint $table) {
            if (!Schema::hasColumn('trades', 'plan_item_id')) {
                $table->uuid('plan_item_id')->nullable()->index();
            }
            if (!Schema::hasColumn('trades', 'exit_reason_code')) {
                $table->string('exit_reason_code', 32)->nullable();
            }
            if (!Schema::hasColumn('trades', 'exit_reason_text')) {
                $table->string('exit_reason_text', 255)->nullable();
            }
            if (!Schema::hasColumn('trades', 'entry_snapshot')) {
                $table->json('entry_snapshot')->nullable();
            }
            if (!Schema::hasColumn('trades', 'exit_snapshot')) {
                $table->json('exit_snapshot')->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('trades', function (Blueprint $table) {
            foreach (['plan_item_id','exit_reason_code','exit_reason_text','entry_snapshot','exit_snapshot'] as $c) {
                if (Schema::hasColumn('trades', $c)) $table->dropColumn($c);
            }
        });
    }
};
