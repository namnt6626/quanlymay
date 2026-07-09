<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nhap_hang_online')) {
            Schema::create('nhap_hang_online', function (Blueprint $table): void {
                $table->id();
                $table->date('ngay_nhap')->index();
                $table->text('ghi_chu')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('nhap_hang_online_chi_tiet')) {
            Schema::create('nhap_hang_online_chi_tiet', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('nhap_hang_online_id')->constrained('nhap_hang_online')->cascadeOnUpdate();
                $table->string('ten_san_pham', 500)->index();
                $table->string('mau', 255)->nullable()->index();
                $table->string('size', 100)->nullable()->index();
                $table->decimal('so_luong', 15, 4);
                $table->decimal('don_gia', 15, 2);
                $table->decimal('thanh_tien', 15, 2);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nhap_hang_online_chi_tiet');
        Schema::dropIfExists('nhap_hang_online');
    }
};
