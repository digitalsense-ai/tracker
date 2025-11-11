<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trades')) {
            Schema::create('trades', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_model_id');
                $table->string('ticker');
                $table->enum('side',['long','short']);
                $table->decimal('entry_price', 18, 6);
                $table->decimal('exit_price', 18, 6)->nullable();
                $table->decimal('qty', 18, 6);
                $table->unsignedInteger('holding_seconds')->nullable();
                $table->decimal('notional_entry', 18, 6)->nullable();
                $table->decimal('notional_exit', 18, 6)->nullable();
                $table->decimal('fees', 18, 6)->default(0);
                $table->decimal('net_pnl', 18, 6)->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
