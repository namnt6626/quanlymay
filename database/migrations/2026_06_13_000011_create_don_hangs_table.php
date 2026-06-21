<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('don_hangs', function (Blueprint $table) {
      $table->id();
      $table->date('ngay_nhan');
      $table->string('ma_don', 100)->index();
      $table->string('ma_kh', 100)->index();
      $table->date('han_giao')->nullable();
      $table->string('kenh_ban', 150)->nullable()->index();
      $table->text('ghi_chu')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('don_hangs');
  }
};
