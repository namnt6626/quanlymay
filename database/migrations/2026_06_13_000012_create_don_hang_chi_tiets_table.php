<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('don_hang_chi_tiets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('don_hang_id')->constrained('don_hangs')->cascadeOnDelete();
      $table->foreignId('mat_hang_id')->constrained('dm_mat_hang')->cascadeOnDelete();
      $table->foreignId('mau_id')->constrained('dm_mau')->cascadeOnDelete();
      $table->foreignId('size_id')->constrained('dm_size')->cascadeOnDelete();
      $table->decimal('so_luong_dat', 15, 4);
      $table->text('ghi_chu')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('don_hang_chi_tiets');
  }
};
