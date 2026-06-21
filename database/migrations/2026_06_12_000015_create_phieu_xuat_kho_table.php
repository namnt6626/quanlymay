<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phieu_xuat_kho', function (Blueprint $table) {
            $table->id();
            $table->string('so_phieu', 50)->index();
            $table->date('ngay_xuat')->index();
            $table->string('kenh_ban');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phieu_xuat_kho');
    }
};
