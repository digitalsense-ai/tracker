<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('saxo_instruments')) {
            return;
        }

        Schema::create('saxo_instruments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uic')->index();           // Saxo "Identifier"
            $table->string('asset_type', 32)->index();            // e.g. Stock
            $table->string('symbol', 64)->index();                // e.g. AAPL:xnas
            $table->string('exchange_id', 32)->nullable()->index(); // e.g. NASDAQ
            $table->string('currency_code', 8)->nullable()->index();
            $table->string('description')->nullable();

            $table->json('tradable_as')->nullable();              // array from API
            $table->boolean('is_tradable')->default(true)->index();

            $table->json('raw')->nullable();                      // full raw object for debugging
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['asset_type','uic'], 'saxo_instruments_asset_uic_unique');
            $table->unique(['symbol'], 'saxo_instruments_symbol_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saxo_instruments');
    }
};
