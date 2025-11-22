<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->nullable();   // e.g. session, risk, filters, retest, management
            $table->string('type')->default('string'); // string|bool|int|float|time|json
            $table->text('value')->nullable();     // store as string; cast in service
            $table->string('label')->nullable();   // nice label for UI
            $table->json('meta')->nullable();      // min/max/step/options/help
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
