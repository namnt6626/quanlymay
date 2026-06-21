<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('permissions', function (Blueprint $table) {
      $table->id();
      $table->string('ma_quyen', 100)->index();
      $table->string('ten_quyen');
      $table->string('module');
      $table->string('action', 50);
      $table->text('mo_ta')->nullable();
      $table->boolean('trang_thai')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('permissions');
  }
};
