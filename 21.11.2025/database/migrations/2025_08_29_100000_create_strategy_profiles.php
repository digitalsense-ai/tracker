<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('strategy_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('settings');
            $table->boolean('enabled')->default(true);
            $table->float('rank')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('strategy_profiles');
    }
};