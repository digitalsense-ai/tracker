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
        Schema::create('saxo_instruments', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('uic')->unique();        // Saxo Identifier
            $table->string('symbol');                   // e.g. GOOGL:xnas
            $table->string('description');              // Full name
            $table->string('asset_type');               // Stock, Etf, etc.
            $table->string('exchange_id');              // NASDAQ, NYSE, MIL
            $table->boolean('is_tradable')->default(true);
            $table->string('currency')->nullable();
            $table->json('raw_json');                   // Store full instrument JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saxo_instruments');
    }
};
