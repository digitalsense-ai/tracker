<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Kolonner (idempotent)
        Schema::table('trades', function (Blueprint $t) {
            if (!Schema::hasColumn('trades', 'strategy_profile_id')) {
                $t->unsignedBigInteger('strategy_profile_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('trades', 'market_session')) {
                $t->string('market_session', 32)->nullable()->after('forecast_type')->index();
            }
            if (!Schema::hasColumn('trades', 'closed_at')) {
                $t->timestamp('closed_at')->nullable()->after('updated_at');
            }
        });

        // 2) Drop eksisterende FK på strategy_profile_id hvis den findes (robust)
        $dbName = DB::getDatabaseName();
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'trades'
              AND COLUMN_NAME = 'strategy_profile_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$dbName]);

        if ($fk && isset($fk->name)) {
            DB::statement("ALTER TABLE `trades` DROP FOREIGN KEY `{$fk->name}`");
        }

        // 3) Indekser (idempotent)
        $ensureIndex = function (string $indexName, string $def) use ($dbName) {
            $exists = DB::selectOne("
                SELECT 1 FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA=? AND TABLE_NAME='trades' AND INDEX_NAME=?
                LIMIT 1
            ", [$dbName, $indexName]);
            if (!$exists) {
                DB::statement("CREATE INDEX `$indexName` ON trades($def)");
            }
        };
        $ensureIndex('ix_trades_profile_created', 'strategy_profile_id, created_at');
        $ensureIndex('ix_trades_session_created', 'market_session, created_at');
        $ensureIndex('ix_trades_ticker_created',  'ticker, created_at');

        // 4) Opret FK igen, kun hvis ikke allerede sat
        $fkExists = DB::selectOne("
            SELECT 1
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA=? AND TABLE_NAME='trades'
              AND COLUMN_NAME='strategy_profile_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$dbName]);

        if (!$fkExists) {
            Schema::table('trades', function (Blueprint $t) {
                $t->foreign('strategy_profile_id')
                  ->references('id')->on('strategy_profiles')
                  ->onUpdate('cascade')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();
        $fk = DB::selectOne("
            SELECT CONSTRAINT_NAME AS name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = 'trades'
              AND COLUMN_NAME = 'strategy_profile_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$dbName]);

        if ($fk && isset($fk->name)) {
            DB::statement("ALTER TABLE `trades` DROP FOREIGN KEY `{$fk->name}`");
        }
        // Ingen kolonne-drop i down() for at undgå datatab
    }
};
