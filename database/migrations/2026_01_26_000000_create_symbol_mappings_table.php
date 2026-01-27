<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('symbol_mappings', function (Blueprint $table) {
   $table->id();
   $table->string('symbol')->unique();
   $table->unsignedBigInteger('saxo_uic');
   $table->string('saxo_asset_type');
   $table->boolean('enabled_for_ai')->default(true);
   $table->integer('priority')->default(100);
   $table->timestamps();
  });
 }
 public function down(): void {
  Schema::dropIfExists('symbol_mappings');
 }
};
