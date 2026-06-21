<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phieu_xuat_kho_chi_tiet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_xuat_kho_id')
                ->constrained('phieu_xuat_kho')
                ->restrictOnDelete();
            $table->foreignId('nhap_kho_id')
                ->constrained('nhap_kho')
                ->restrictOnDelete();
            $table->decimal('so_luong_xuat', 14, 4);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phieu_xuat_kho_chi_tiet');
    }
};
