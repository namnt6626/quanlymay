<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nhap_kho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_id')
                ->constrained('qc')
                ->restrictOnDelete();
            $table->date('ngay_nhap')->index();
            $table->decimal('so_luong_nhap', 14, 4);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nhap_kho');
    }
};
