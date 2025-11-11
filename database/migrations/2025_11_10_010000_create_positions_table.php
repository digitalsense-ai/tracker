<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('positions')) {
            Schema::create('positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_model_id');
                $table->string('ticker');
                $table->enum('side', ['long','short']);
                $table->decimal('qty', 18, 6)->default(0);
                $table->decimal('avg_price', 18, 6)->default(0);
                $table->decimal('stop_price', 18, 6)->nullable();
                $table->decimal('target_price', 18, 6)->nullable();
                $table->decimal('leverage', 8, 2)->nullable();
                $table->decimal('margin', 18, 6)->nullable();
                $table->decimal('unrealized_pnl', 18, 6)->default(0);
                $table->enum('status', ['open','closed'])->default('open');
                $table->timestamp('opened_at')->nullable();
                $table->timestamps();
                $table->foreign('ai_model_id')->references('id')->on('ai_models')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
