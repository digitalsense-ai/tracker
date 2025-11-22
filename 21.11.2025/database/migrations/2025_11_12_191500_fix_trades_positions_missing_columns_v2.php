<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trades')) {
            Schema::table('trades', function (Blueprint $table) {
                if (!Schema::hasColumn('trades', 'ai_model_id')) { $table->unsignedBigInteger('ai_model_id')->nullable(); $table->index('ai_model_id'); }
                if (!Schema::hasColumn('trades', 'ticker')) { $table->string('ticker', 20)->nullable(); }
                if (!Schema::hasColumn('trades', 'side')) { $table->enum('side', ['long','short'])->nullable(); }
                if (!Schema::hasColumn('trades', 'entry_price')) { $table->decimal('entry_price', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'exit_price')) { $table->decimal('exit_price', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'qty')) { $table->decimal('qty', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'notional_entry')) { $table->decimal('notional_entry', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'notional_exit')) { $table->decimal('notional_exit', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'fees')) { $table->decimal('fees', 18, 6)->nullable()->default(0); }
                if (!Schema::hasColumn('trades', 'net_pnl')) { $table->decimal('net_pnl', 18, 6)->nullable(); }
                if (!Schema::hasColumn('trades', 'opened_at')) { $table->timestamp('opened_at')->nullable(); }
                if (!Schema::hasColumn('trades', 'closed_at')) { $table->timestamp('closed_at')->nullable(); }
            });
        }

        if (Schema::hasTable('positions')) {
            Schema::table('positions', function (Blueprint $table) {
                if (!Schema::hasColumn('positions', 'ai_model_id')) { $table->unsignedBigInteger('ai_model_id')->nullable(); $table->index('ai_model_id'); }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive
    }
};
