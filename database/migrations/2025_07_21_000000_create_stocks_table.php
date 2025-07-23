<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('ticker')->index();
            $table->float('gap');
            $table->float('rvol');
            $table->unsignedBigInteger('volume');
            $table->string('forecast')->nullable();
            $table->string('status')->default('forecast');
            $table->float('entry_price')->nullable();
            $table->float('sl')->nullable();
            $table->float('tp1')->nullable();
            $table->float('tp2')->nullable();
            $table->float('tp3')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('stocks');
    }
};
