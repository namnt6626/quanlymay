<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('phan_bo_may', function (Blueprint $table) {
      $table->id();
      $table->foreignId('cat_id')
        ->constrained('cat')
        ->restrictOnDelete();
      $table->date('ngay_phan_bo')->index();
      $table->foreignId('don_vi_may_id')
        ->constrained('dm_don_vi_may')
        ->restrictOnDelete();
      $table->decimal('so_luong_giao', 14, 4);
      $table->text('ghi_chu')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('phan_bo_may');
  }
};
