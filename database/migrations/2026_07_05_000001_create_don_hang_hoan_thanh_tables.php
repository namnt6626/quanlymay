<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_hang_hoan_thanh', function (Blueprint $table): void {
            $table->id();
            $table->date('ngay_hoan_thanh')->index();
            $table->string('ten_san_pham', 500);
            $table->string('ten_kho', 255)->nullable()->index();
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ngay_hoan_thanh', 'ten_kho']);
        });

        Schema::create('don_hang_hoan_thanh_chi_tiet', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('don_hang_hoan_thanh_id')->constrained('don_hang_hoan_thanh')->cascadeOnUpdate();
            $table->string('mau', 255)->nullable();
            $table->string('size', 100)->nullable();
            $table->string('phan_loai_goc', 500)->nullable();
            $table->decimal('so_luong', 15, 4);
            $table->decimal('thanh_tien', 15, 2);
            $table->enum('nguon', ['excel', 'thu_cong'])->default('thu_cong');
            $table->dateTime('thoi_gian_tao_goc')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['mau', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_hang_hoan_thanh_chi_tiet');
        Schema::dropIfExists('don_hang_hoan_thanh');
    }
};
