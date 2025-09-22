<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Ensure column exists + index
        if (!Schema::hasColumn('trades','strategy_profile_id')) {
            Schema::table('trades', function (Blueprint $t) {
                $t->unsignedBigInteger('strategy_profile_id')->nullable()->after('id')->index();
            });
        } else {
            try {
                Schema::table('trades', function (Blueprint $t) {
                    $t->index('strategy_profile_id');
                });
            } catch (\Throwable $e) {}
        }

        // 2) Block if NULLs exist
        $nulls = DB::table('trades')->whereNull('strategy_profile_id')->count();
        if ($nulls > 0) {
            throw new \RuntimeException("Cannot enforce NOT NULL: trades.strategy_profile_id has {$nulls} NULL rows. Backfill first.");
        }

        // 3) Make NOT NULL (raw SQL, no DBAL)
        DB::statement("ALTER TABLE `trades` MODIFY `strategy_profile_id` BIGINT UNSIGNED NOT NULL");

        // 4) Add FK if not exists
        $fkExists = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'trades'
              AND COLUMN_NAME = 'strategy_profile_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        if (($fkExists->c ?? 0) == 0) {
            DB::statement("
                ALTER TABLE `trades`
                ADD CONSTRAINT `fk_trades_strategy_profile_id`
                FOREIGN KEY (`strategy_profile_id`)
                REFERENCES `strategy_profiles`(`id`)
                ON DELETE RESTRICT ON UPDATE RESTRICT
            ");
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `trades` DROP FOREIGN KEY `fk_trades_strategy_profile_id`");
        } catch (\Throwable $e) {}
        DB::statement("ALTER TABLE `trades` MODIFY `strategy_profile_id` BIGINT UNSIGNED NULL");
        try {
            Schema::table('trades', function (Blueprint $t) {
                $t->dropIndex(['strategy_profile_id']);
            });
        } catch (\Throwable $e) {}
    }
};
