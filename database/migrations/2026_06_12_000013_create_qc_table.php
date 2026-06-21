<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('qc', function (Blueprint $table) {
      $table->id();
      $table->foreignId('phan_bo_may_id')
        ->constrained('phan_bo_may')
        ->restrictOnDelete();
      $table->date('ngay_qc')->index();
      $table->decimal('so_luong_qc', 14, 4);
      $table->decimal('so_luong_dat', 14, 4);
      $table->decimal('so_luong_loi', 14, 4);
      $table->decimal('so_luong_hong', 14, 4);
      $table->text('ghi_chu')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('qc');
  }
};
