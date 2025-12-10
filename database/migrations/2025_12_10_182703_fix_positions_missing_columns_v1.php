<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('positions')) {
            Schema::table('positions', function (Blueprint $table) {                
                if (!Schema::hasColumn('positions', 'closed_at')) { $table->timestamp('closed_at')->nullable()->after('opened_at'); }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
